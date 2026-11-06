<?php

namespace Tests\Resources;

use Bonfire\Fields\Field;
use Bonfire\Resources\Resource;
use Bonfire\Users\User;
use CodeIgniter\Config\Factories;
use CodeIgniter\Exceptions\PageNotFoundException;
use Config\Services;
use InvalidArgumentException;
use Tests\Support\Models\CategoryModel;
use Tests\Support\Modules\Categories\CategoryResource;
use Tests\Support\TestCase;

/**
 * The list of a resource: Categories, which is only a resource class and two registration lines.
 *
 * @internal
 */
final class ResourceListTest extends TestCase
{
    private const SERVICES = ['auth', 'bonfire', 'menus', 'widgets', 'resources'];

    protected $refresh = true;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withTestResources();
        setting('Site.siteOnline', true);
        setting('Site.perPage', 10);

        foreach (self::SERVICES as $service) {
            Services::resetSingle($service);
        }

        // The admin group has categories.list; installs add such keys to their own matrix.
        $this->adminGroupHas(['admin.access', 'categories.list']);

        $this->admin = $this->createUser();
        $this->admin->addGroup('admin');

        foreach (['Delta', 'Alpha', 'Charlie', 'Bravo', 'Echo'] as $name) {
            model(CategoryModel::class)->insert(['name' => $name]);
        }
    }

    protected function tearDown(): void
    {
        foreach (self::SERVICES as $service) {
            Services::resetSingle($service);
        }

        Factories::reset('config');

        parent::tearDown();
    }

    public function testAnAdminWithTheListPermissionSeesTheSidebarItemAndTheNames(): void
    {
        $body = $this->visit('admin/categories');

        $this->assertMatchesRegularExpression('#href="[^"]*/admin/categories"#', $this->sidebar($body));
        $this->assertSame(['Delta', 'Alpha', 'Charlie', 'Bravo', 'Echo'], $this->names($body));
    }

    public function testAUserWithoutTheListPermissionIsRefusedAndDoesNotSeeTheMenuItem(): void
    {
        $this->adminGroupHas(['admin.access']);

        $response = $this->actingAs($this->admin)->get('admin/categories');

        $response->assertRedirect();
        $response->assertSessionHas('error', lang('Bonfire.notAuthorized'));
        $this->assertStringNotContainsString('data-row', (string) $response->response()->getBody());

        $this->assertDoesNotMatchRegularExpression('#href="[^"]*/admin/categories"#', $this->sidebar($this->visit('admin')));
    }

    public function testTheListIsPaginated(): void
    {
        setting('Site.perPage', 2);

        $first = $this->visit('admin/categories');

        $this->assertSame(['Delta', 'Alpha'], $this->names($first));

        $second = $this->visit($this->pagerLinks($first)[0]);

        $this->assertSame(['Charlie', 'Bravo'], $this->names($second));
        $this->assertSame(['Previous', 'Next'], $this->pagerLabels($second));
    }

    public function testAColumnSortsInBothDirections(): void
    {
        $ascending = $this->visit($this->sortLink($this->visit('admin/categories')));

        $this->assertSame(['Alpha', 'Bravo', 'Charlie', 'Delta', 'Echo'], $this->names($ascending));
        $this->assertStringContainsString('aria-sort="ascending"', $ascending);

        $descending = $this->visit($this->sortLink($ascending));

        $this->assertSame(['Echo', 'Delta', 'Charlie', 'Bravo', 'Alpha'], $this->names($descending));
        $this->assertStringContainsString('aria-sort="descending"', $descending);
    }

    public function testThePagerKeepsTheSorting(): void
    {
        setting('Site.perPage', 2);

        $first = $this->visit('admin/categories?sort=name&dir=desc');

        $this->assertSame(['Echo', 'Delta'], $this->names($first));
        $this->assertSame(['Charlie', 'Bravo'], $this->names($this->visit($this->pagerLinks($first)[0])));
    }

    public function testASortThatIsNotAColumnIsIgnored(): void
    {
        $body = $this->visit('admin/categories?sort=password;drop&dir=desc');

        $this->assertSame(['Delta', 'Alpha', 'Charlie', 'Bravo', 'Echo'], $this->names($body));
    }

    public function testSortingAndPagingRefreshOverHtmxWithOnlyTheTable(): void
    {
        setting('Site.perPage', 2);

        $page = $this->visit('admin/categories');

        // The list marks where htmx puts what it loads.
        $this->assertStringContainsString('id="content-list"', $page);
        $this->assertStringContainsString('hx-target="#content-list"', $page);

        $sorted = $this->visit($this->sortLink($page), htmx: true);

        $this->assertSame(['Alpha', 'Bravo'], $this->names($sorted));
        $this->assertStringNotContainsString('<html', $sorted);
        $this->assertStringNotContainsString('id="sidebar"', $sorted);

        // The links htmx follows are the ones a browser without JavaScript follows.
        $this->assertSame([$this->sortLink($sorted), $this->pagerLinks($sorted)[0]], $this->htmxLinks($sorted));

        $next = $this->visit($this->pagerLinks($sorted)[0], htmx: true);

        $this->assertSame(['Charlie', 'Delta'], $this->names($next));
        $this->assertStringNotContainsString('<html', $next);
    }

    public function testWithOnlyTheListPageTheCreateAndEditUrlsAreNotFoundAndThereIsNoNewButton(): void
    {
        $body = $this->visit('admin/categories');

        $this->assertStringNotContainsString('admin/categories/new', $body);
        $this->assertMatchesRegularExpression('#<div class="bf-page-head-actions">\s*</div>#', $body);

        foreach (['admin/categories/new', 'admin/categories/1'] as $url) {
            try {
                $this->actingAs($this->admin)->get($url);
                $this->fail("{$url} should not be found.");
            } catch (PageNotFoundException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testTheDefaultColumnsLeaveOutTextareaAndMarkdownFields(): void
    {
        $resource = new class () extends Resource {
            public function fields(): array
            {
                return [
                    new Field('title', 'text', 'Title', '', null, []),
                    new Field('summary', 'textarea', 'Summary', '', null, []),
                    new Field('body', 'markdown', 'Body', '', null, []),
                    new Field('stock', 'integer', 'Stock', '', null, []),
                ];
            }
        };

        $this->assertSame(['title', 'stock'], array_map(static fn (Field $column): string => $column->handle, $resource->columns()));
    }

    public function testAResourceCanOverrideItsColumns(): void
    {
        $resource = new class () extends Resource {
            public function fields(): array
            {
                return [new Field('title', 'text', 'Title', '', null, []), new Field('body', 'markdown', 'Body', '', null, [])];
            }

            public function columns(): array
            {
                return $this->fields();
            }
        };

        $this->assertCount(2, $resource->columns());
    }

    public function testAListPhpBesideTheResourceReplacesThePackageView(): void
    {
        $plain = $this->visit('admin/categories');

        $this->assertStringNotContainsString('data-custom-list', $plain);

        $custom = $this->visit('admin/shelves');

        $this->assertStringContainsString('data-custom-list="shelves"', $custom);
        $this->assertSame(['Delta', 'Alpha', 'Charlie', 'Bravo', 'Echo'], $this->names($custom));
    }

    public function testOnlyAResourceClassCanBeRegistered(): void
    {
        $this->expectException(InvalidArgumentException::class);

        service('resources')->register(CategoryModel::class);
    }

    public function testRegisteringKeepsTheResourceBySlug(): void
    {
        $this->visit('admin');

        $this->assertInstanceOf(CategoryResource::class, service('resources')->get('categories'));
        $this->assertNull(service('resources')->get('nope'));
    }

    /**
     * Gives the admin group these permissions and no others.
     *
     * @param list<string> $permissions
     */
    private function adminGroupHas(array $permissions): void
    {
        $config                  = config('AuthGroups');
        $config->matrix['admin'] = $permissions;
        Factories::injectMock('config', 'AuthGroups', $config);
    }

    /**
     * Opens the page as the admin, and returns its body: the response object is shared, so the next
     * request changes what an earlier one's response reads.
     */
    private function visit(string $url, bool $htmx = false): string
    {
        [$path, $query] = array_pad(explode('?', $url, 2), 2, '');
        parse_str($query, $params);

        // A pager remembers its page, and a browser's next request starts without it.
        Services::resetSingle('pager');

        $test = $htmx ? $this->withHeaders(['HX-Request' => 'true']) : $this;

        return (string) $test->actingAs($this->admin)->get($path, $params)->response()->getBody();
    }

    /**
     * The sidebar only, since the dashboard's widget palette names things too.
     */
    private function sidebar(string $body): string
    {
        preg_match('#<aside id="sidebar".*?</aside>#s', $body, $match);

        return $match[0] ?? '';
    }

    /**
     * The names in the list, in the order shown.
     *
     * @return list<string>
     */
    private function names(string $body): array
    {
        preg_match_all('#<td data-cell="name">(.*?)</td>#', $body, $matches);

        return $matches[1];
    }

    /**
     * The link of the column heading, as a request path.
     */
    private function sortLink(string $body): string
    {
        preg_match('#<th data-column="name"[^>]*>\s*<a href="([^"]+)"#', $body, $match);

        return $this->path($match[1] ?? '');
    }

    /**
     * The links of the pager that go somewhere, as request paths.
     *
     * @return list<string>
     */
    private function pagerLinks(string $body): array
    {
        preg_match('#<nav class="bf-list-pager".*?</nav>#s', $body, $nav);
        preg_match_all('#<a [^>]*href="([^"]+)"#', $nav[0] ?? '', $matches);

        return array_map($this->path(...), $matches[1]);
    }

    /**
     * The labels of the pager's buttons, whether they go anywhere or not.
     *
     * @return list<string>
     */
    private function pagerLabels(string $body): array
    {
        preg_match('#<nav class="bf-list-pager".*?</nav>#s', $body, $nav);
        preg_match_all('#<(?:a|span) class="btn[^>]*>([^<]+)</#', $nav[0] ?? '', $matches);

        return $matches[1];
    }

    /**
     * Where htmx would get the list from: each link's hx-get, as a request path.
     *
     * @return list<string>
     */
    private function htmxLinks(string $body): array
    {
        preg_match_all('#hx-get="([^"]+)"#', $body, $matches);

        return array_map($this->path(...), $matches[1]);
    }

    private function path(string $url): string
    {
        return ltrim(str_replace(['http://example.com', 'index.php'], '', html_entity_decode($url)), '/');
    }
}
