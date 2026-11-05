<?php

namespace Tests\Content;

use Bonfire\Content\Entry;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use Tests\Support\Content\ContentTestCase;

/**
 * Follows the "Listing a collection" recipe in docs/content/query_api.md through real requests.
 *
 * @internal
 */
final class CollectionIndexTest extends ContentTestCase
{
    private const DOCS = __DIR__ . '/../../docs/content/query_api.md';
    private const VIEW = APPPATH . 'Views/blog/index.php';

    private bool $madeViewFolder = false;
    private string $routeFile    = '';

    protected function setUp(): void
    {
        parent::setUp();

        Time::setTestNow('2026-06-01 12:00:00');

        $this->reindexBlogWith("route: /blog/{slug}\n");
        $this->installRecipe();
    }

    protected function tearDown(): void
    {
        Time::setTestNow();

        @unlink(self::VIEW);
        if ($this->madeViewFolder) {
            rmdir(dirname(self::VIEW));
        }
        @unlink($this->routeFile);

        service('superglobals')->setGetArray([]);
        Services::resetSingle('pager');
        Services::resetSingle('routes');
        Services::resetSingle('router');

        parent::tearDown();
    }

    public function testTheFirstPageListsTheFirstEntriesOnly(): void
    {
        $page = $this->visit('blog');

        $page->assertOK();
        $this->assertSame(['second-post'], $this->listed($page));
    }

    public function testTheSecondPageListsTheNextEntries(): void
    {
        $page = $this->visit('blog', ['page' => '2']);

        $page->assertOK();
        $this->assertSame(['first-post'], $this->listed($page));
    }

    public function testThePagerLinksReachTheSecondPage(): void
    {
        $first = $this->visit('blog');

        $this->assertSame(1, preg_match('/href="([^"]*[?&]page=2[^"]*)"/', $first->getBody(), $link), 'The first page has no link to page 2.');

        // The link is a full URL, so follow its path and query as a browser would.
        $url = parse_url(html_entity_decode($link[1]));
        parse_str($url['query'] ?? '', $query);

        $second = $this->visit(preg_replace('#^/?(index\.php/)?#', '', $url['path'] ?? ''), $query);

        $this->assertSame(['first-post'], $this->listed($second));
        $this->assertMatchesRegularExpression('/href="([^"]*[?&]page=1[^"]*)"/', $second->getBody(), 'The second page has no link back to page 1.');
    }

    public function testPaginateReturnsAPlainList(): void
    {
        $posts = content()->collection('blog')->published()->paginate(1);

        $this->assertTrue(array_is_list($posts));
        $this->assertContainsOnlyInstancesOf(Entry::class, $posts);
    }

    /**
     * The slugs of the entries a page links to, in order.
     *
     * @return list<string>
     */
    private function listed(TestResponse $page): array
    {
        preg_match_all('#<a href="/blog/([^"]+)"#', $page->getBody(), $matches);

        return $matches[1];
    }

    /**
     * A GET like a browser's. The pager reads the page from the superglobals, which the feature test
     * leaves empty, and keeps its state for the life of the process, which is one request.
     *
     * @param array<string, string> $query
     */
    private function visit(string $path, array $query = []): TestResponse
    {
        Services::resetSingle('pager');
        service('superglobals')->setGetArray($query);

        return $this->get($path, $query);
    }

    /**
     * Puts the recipe's route and view where an app has them, with a page size of one so that the
     * fixture's two published entries make two pages.
     */
    private function installRecipe(): void
    {
        $route = str_replace('paginate(10)', 'paginate(1)', $this->block('$routes->get('));
        $this->assertStringContainsString('paginate(1)', $route);

        $this->routeFile = tempnam(sys_get_temp_dir(), 'bonfire-routes-');
        file_put_contents($this->routeFile, "<?php\n{$route}");

        if (! is_dir(dirname(self::VIEW))) {
            mkdir(dirname(self::VIEW));
            $this->madeViewFolder = true;
        }
        file_put_contents(self::VIEW, $this->block('$pager->links()'));

        Services::resetSingle('routes');
        Services::resetSingle('router');

        $routes = service('routes');
        include $this->routeFile;
    }

    /**
     * The php block under the recipe's heading that contains this text.
     */
    private function block(string $contains): string
    {
        $docs = (string) file_get_contents(self::DOCS);
        $this->assertStringContainsString('### Listing a collection', $docs);

        $recipe = substr($docs, (int) strpos($docs, '### Listing a collection'));
        preg_match_all('/^```php\n(.*?)^```$/ms', $recipe, $matches);

        foreach ($matches[1] as $block) {
            if (str_contains($block, $contains)) {
                return $block;
            }
        }

        $this->fail("The recipe has no php block with {$contains}.");
    }
}
