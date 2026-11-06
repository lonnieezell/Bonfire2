<?php

namespace Tests\Resources;

use Bonfire\Actions\Models\ActionRunModel;
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
    private const SERVICES = ['auth', 'bonfire', 'menus', 'widgets', 'resources', 'recycler'];

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

    public function testTheSearchBoxNarrowsTheListToRecordsMatchingAnySearchColumn(): void
    {
        $page = $this->visit('admin/categories');

        $this->assertMatchesRegularExpression('#<input type="search" name="search"[^>]*value=""#', $page);

        // In the name column
        $this->assertSame(['Alpha'], $this->names($this->visit('admin/categories?search=lph')));

        // In the status column, which is the other search column
        $this->setStatus('Bravo', 'retired');
        $this->assertSame(['Bravo'], $this->names($this->visit('admin/categories?search=retir')));

        // The box keeps what was typed
        $this->assertMatchesRegularExpression('#name="search"[^>]*value="retir"#', $this->visit('admin/categories?search=retir'));
        $this->assertSame([], $this->names($this->visit('admin/categories?search=nothing-like-this')));
    }

    public function testASearchTreatsPercentAndUnderscoreAsPlainCharacters(): void
    {
        model(CategoryModel::class)->insert(['name' => '100% Cotton']);

        $this->assertSame(['100% Cotton'], $this->names($this->visit('admin/categories?search=' . urlencode('0%'))));
        $this->assertSame([], $this->names($this->visit('admin/categories?search=' . urlencode('_'))));
    }

    public function testASearchWithNoMatchSaysSoInsteadOfThatTheListIsEmpty(): void
    {
        $body = $this->visit('admin/categories?search=nothing-like-this');

        $this->assertStringContainsString(lang('Resources.noMatches'), $body);
        $this->assertStringNotContainsString(lang('Resources.empty'), $body);
    }

    public function testTheFiltersRenderThroughTheFiltersCellAndNarrowTheList(): void
    {
        $this->setStatus('Alpha', 'draft');
        $this->setStatus('Bravo', 'draft');
        $this->setStatus('Echo', 'retired');

        $page = $this->visit('admin/categories');

        // Options from filters(), and the ones that a method of the resource computes
        $this->assertStringContainsString('<section class="filters">', $page);
        $this->assertStringContainsString('name="filters[status][draft]"', $page);
        $this->assertStringContainsString('name="filters[name][Bravo]"', $page);

        $this->assertSame(['Alpha', 'Bravo'], $this->names($this->visit('admin/categories?filters[status][draft]=draft&sort=name&dir=asc')));
        $this->assertSame(['Alpha', 'Bravo', 'Echo'], $this->names($this->visit('admin/categories?filters[status][draft]=draft&filters[status][retired]=retired&sort=name&dir=asc')));
        $this->assertSame(['Bravo'], $this->names($this->visit('admin/categories?filters[status][draft]=draft&filters[name][Bravo]=Bravo')));
    }

    public function testOnlyTheColumnsThatFiltersNamesCanFilter(): void
    {
        $this->assertSame(['Delta', 'Alpha', 'Charlie', 'Bravo', 'Echo'], $this->names($this->visit('admin/categories?filters[id][1]=1&filters[unknown]=x')));
    }

    public function testSearchFiltersSortingAndPagingCombineAndSurviveHtmxRefreshes(): void
    {
        setting('Site.perPage', 2);

        foreach (['Alpha', 'Bravo', 'Charlie'] as $name) {
            $this->setStatus($name, 'draft');
        }

        // The search box and the filter panel are outside the table that htmx replaces, and htmx sends them along
        $full = $this->visit('admin/categories');

        $this->assertMatchesRegularExpression('#hx-include="[^"]*\.bf-toolbar-form[^"]*\.filter-check"#', $full);
        $this->assertStringContainsString('hx-trigger="input delay:300ms from:[name=search]"', $full);

        $page = $this->visit('admin/categories?search=a&filters[status][draft]=draft&sort=name&dir=desc');

        // Delta (live) and Echo (no "a") are out, and the rest are sorted and paged
        $this->assertSame(['Charlie', 'Bravo'], $this->names($page));

        // The sort and pager links keep the search and the filters, with and without htmx
        $this->assertSame(['Alpha'], $this->names($this->visit($this->pagerLinks($page)[0])));
        $this->assertSame(['Alpha'], $this->names($this->visit($this->pagerLinks($page)[0], htmx: true)));

        $ascending = $this->visit($this->sortLink($page), htmx: true);

        $this->assertSame(['Alpha', 'Bravo'], $this->names($ascending));
        $this->assertStringNotContainsString('<html', $ascending);
        $this->assertSame(['Charlie'], $this->names($this->visit($this->pagerLinks($ascending)[0], htmx: true)));
    }

    public function testTheGlobalSearchFindsARecordByASearchColumnAndLinksToIt(): void
    {
        $this->setStatus('Charlie', 'retired');

        $found = $this->search('lph')['Categories'];

        $this->assertSame(['Alpha'], array_column($found, 'label'));

        // The link opens the list narrowed to the record
        $this->assertSame(['Alpha'], $this->names($this->visit($this->path($found[0]['url']))));

        // Another search column
        $this->assertSame(['Charlie'], array_column($this->search('retire')['Categories'], 'label'));
    }

    public function testTheGlobalSearchLeavesOutDeletedRecordsAndWhatTheUserMayNotList(): void
    {
        model(CategoryModel::class)->where('name', 'Alpha')->delete();

        $this->assertSame([], $this->search('lph')['Categories']);

        $this->adminGroupHas(['admin.access']);

        $this->assertArrayNotHasKey('Categories', $this->search('Bravo'));
    }

    public function testASoftDeletedRecordShowsInTheRecyclerAndCanBeRestored(): void
    {
        $this->adminGroupHas(['admin.access', 'categories.list', 'recycler.view']);

        $alpha = model(CategoryModel::class)->where('name', 'Alpha')->first();
        model(CategoryModel::class)->delete($alpha->id);

        $this->assertNotContains('Alpha', $this->names($this->visit('admin/categories')));

        $recycler = $this->visit(route_to('recycler') . '?r=categories');

        $this->assertStringContainsString('Alpha', $recycler);
        $this->assertStringNotContainsString('Bravo', $recycler);

        $this->actingAs($this->admin)->post(route_to('recycler-restore', 'categories', $alpha->id), [csrf_token() => csrf_hash()]);

        $this->assertContains('Alpha', $this->names($this->visit('admin/categories')));
        $this->assertStringNotContainsString('Alpha', $this->visit(route_to('recycler') . '?r=categories'));
    }

    public function testTheRecyclerShowsTheSearchColumnsOfTheResource(): void
    {
        $this->visit('admin');

        $this->assertSame(['name', 'status'], service('recycler')->find('categories')->columns());
    }

    public function testAResourceWhoseModelDoesNotSoftDeleteIsLeftOutOfTheRecycler(): void
    {
        $resource = new class () extends Resource {
            public string $model      = ActionRunModel::class;
            public string $slug       = 'runs';
            public string $permission = 'runs';
            public array $pages       = [];
        };

        service('resources')->register($resource::class);

        $this->assertNull(service('recycler')->resources()['runs'] ?? null);
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

    private function setStatus(string $name, string $status): void
    {
        model(CategoryModel::class)->where('name', $name)->set(['status' => $status])->update();
    }

    /**
     * What the admin's global search finds for the term: the label and link of each result, by section title.
     *
     * @return array<string, list<array{label: string, url: string}>>
     */
    private function search(string $term): array
    {
        $body = (string) $this->actingAs($this->admin)->post(ADMIN_AREA . '/search', ['search_term' => $term])->response()->getBody();

        $results = [];

        foreach (explode('<fieldset>', $body) as $section) {
            if (preg_match('#<legend>(.*?)</legend>#', $section, $title) !== 1) {
                continue;
            }

            preg_match_all('#<li data-search-result><a href="([^"]+)">(.*?)</a>#', $section, $found, PREG_SET_ORDER);

            $results[$title[1]] = array_map(static fn (array $result): array => ['label' => $result[2], 'url' => $result[1]], $found);
        }

        return $results;
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
