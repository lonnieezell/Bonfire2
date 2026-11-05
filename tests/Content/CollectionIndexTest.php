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
    private const DOCS  = __DIR__ . '/../../docs/content/query_api.md';
    private const VIEW  = APPPATH . 'Views/blog/index.php';
    private const ROUTE = 'collection_index_routes.php';

    private bool $madeViewFolder = false;

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
        @unlink(sys_get_temp_dir() . '/' . self::ROUTE);

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
        $page->assertSee('Second post', 'h2');
        $page->assertDontSee('First post');
        $page->assertDontSee('Draft post');
        $page->assertDontSee('Future post');
    }

    public function testTheSecondPageListsTheNextEntries(): void
    {
        $page = $this->visit('blog', ['page' => '2']);

        $page->assertOK();
        $page->assertSee('First post', 'h2');
        $page->assertSee('Where it starts');
        $page->assertDontSee('Second post');
    }

    public function testThePagerLinksReachTheSecondPage(): void
    {
        $first = $this->visit('blog');

        $this->assertSame(1, preg_match('/href="([^"]*[?&]page=2[^"]*)"/', $first->getBody(), $link), 'The first page has no link to page 2.');

        // The link is a full URL, so follow its path and query as a browser would.
        $url = parse_url(html_entity_decode($link[1]));
        parse_str($url['query'] ?? '', $query);

        $second = $this->visit(preg_replace('#^/?(index\.php/)?#', '', $url['path'] ?? ''), $query);

        $second->assertSee('First post', 'h2');
        $second->assertDontSee('Second post');
        $this->assertMatchesRegularExpression('/href="([^"]*[?&]page=1[^"]*)"/', $second->getBody(), 'The second page has no link back to page 1.');
    }

    public function testAnEntryLinksToItsPage(): void
    {
        $this->assertStringContainsString('href="/blog/second-post"', $this->visit('blog')->getBody());
    }

    public function testPaginateReturnsAPlainList(): void
    {
        $posts = content()->collection('blog')->published()->paginate(1);

        $this->assertTrue(array_is_list($posts));
        $this->assertContainsOnlyInstancesOf(Entry::class, $posts);
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

        return $this->get($path, $query === [] ? null : $query);
    }

    /**
     * Puts the recipe's route and view where an app has them, with a page size of one so that the
     * fixture's two published entries make two pages.
     */
    private function installRecipe(): void
    {
        $route = str_replace('paginate(10)', 'paginate(1)', $this->block(0));
        $this->assertStringContainsString('paginate(1)', $route);

        file_put_contents(sys_get_temp_dir() . '/' . self::ROUTE, "<?php\n{$route}");

        if (! is_dir(dirname(self::VIEW))) {
            mkdir(dirname(self::VIEW));
            $this->madeViewFolder = true;
        }
        file_put_contents(self::VIEW, $this->block(1));

        Services::resetSingle('routes');
        Services::resetSingle('router');

        $routes = service('routes');
        include sys_get_temp_dir() . '/' . self::ROUTE;
    }

    /**
     * The nth php block under the recipe's heading.
     */
    private function block(int $n): string
    {
        $docs = (string) file_get_contents(self::DOCS);
        $this->assertStringContainsString('### Listing a collection', $docs);

        $recipe = substr($docs, (int) strpos($docs, '### Listing a collection'));
        preg_match_all('/^```php\n(.*?)^```$/ms', $recipe, $matches);

        $this->assertArrayHasKey($n, $matches[1], "The recipe has no php block number {$n}.");

        return $matches[1][$n];
    }
}
