<?php

namespace Tests\Commands;

use CodeIgniter\I18n\Time;
use CodeIgniter\Test\StreamFilterTrait;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use Tests\Support\Content\ContentTestCase;

/**
 * @internal
 */
final class ContentScaffoldTest extends ContentTestCase
{
    use StreamFilterTrait;

    private const VIEWS      = APPPATH . 'Views/blog/';
    private const CONTROLLER = APPPATH . 'Controllers/Blog.php';

    protected function setUp(): void
    {
        parent::setUp();

        Time::setTestNow('2026-06-01 12:00:00');
        Services::resetSingle('viewMeta');
        $this->resetRouting();
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        Services::resetSingle('viewMeta');
        Services::resetSingle('pager');
        service('superglobals')->setGetArray([]);
        $this->resetRouting();

        foreach (['index.php', 'show.php'] as $file) {
            @unlink(self::VIEWS . $file);
        }
        @rmdir(self::VIEWS);
        @unlink(self::CONTROLLER);

        parent::tearDown();

        // So that no fallback route is left in the routes for a later test.
        service('routes')->loadRoutes();
    }

    public function testAnUnknownCollectionIsAnErrorAndWritesNothing(): void
    {
        command('content:scaffold nope');

        $this->assertStringContainsString('Unknown collection: nope', $this->getStreamFilterBuffer());
        $this->assertDirectoryDoesNotExist(APPPATH . 'Views/nope');
    }

    public function testAnExistingFileIsSkippedAndNamed(): void
    {
        mkdir(self::VIEWS);
        file_put_contents(self::VIEWS . 'show.php', 'mine');

        command('content:scaffold blog');

        $this->assertSame('mine', file_get_contents(self::VIEWS . 'show.php'));
        $this->assertFileExists(self::VIEWS . 'index.php');
        $this->assertMatchesRegularExpression('#Skipped.*Views/blog/show\.php#', $this->getStreamFilterBuffer());
    }

    public function testTheShowViewRendersTheEntryWithItsSeoTags(): void
    {
        command('content:scaffold blog');
        $this->serveBlog("template: blog/show\n");

        $page = $this->get('blog/first-post');

        $page->assertOK();
        $page->assertSee('<h1>First post</h1>');
        $page->assertSee('Where it starts');
        $page->assertSee('<em>post</em>');
        $page->assertSee('rel="canonical" href="' . base_url('/blog/first-post') . '"');
        $page->assertSee('<title>First post</title>');
    }

    public function testAnEntriesFieldLinksOnlyItsPublishedEntries(): void
    {
        file_put_contents($this->blueprints . 'collections/blog/post.yaml', "  - handle: related\n    type: entries\n", FILE_APPEND);
        $this->writeFile('collections/blog/2026-01-10.first-post.md', "---\nid: 11111111-1111-4111-8111-111111111111\ntitle: First post\nrelated:\n  - 22222222-2222-4222-8222-222222222222\n  - 33333333-3333-4333-8333-333333333333\n---\nThe first post.\n");

        command('content:scaffold blog');
        $this->serveBlog("template: blog/show\n");

        $page = $this->get('blog/first-post');

        $page->assertSee('<a href="/blog/second-post">Second post</a>');
        $page->assertDontSee('/blog/draft-post');
    }

    public function testTheIndexViewListsAPageOfEntriesWithPagerLinks(): void
    {
        command('content:scaffold blog');
        $this->serveBlog("template: blog/show\n");
        $this->addPrintedIndexRoute();

        $first = $this->visit('blog');
        $first->assertOK();
        $this->assertSame(['/blog/second-post'], $this->listed($first));
        $this->assertMatchesRegularExpression('/href="[^"]*page=2"/', $first->getBody());

        $this->assertSame(['/blog/first-post'], $this->listed($this->visit('blog', ['page' => '2'])));
    }

    public function testWithControllerTheCollectionHandsOffToTheWrittenController(): void
    {
        command('content:scaffold blog --controller');
        $this->assertFileExists(self::CONTROLLER);
        $this->assertStringContainsString('controller: App\\Controllers\\Blog::show', $this->getStreamFilterBuffer());

        $this->serveBlog("controller: App\\Controllers\\Blog::show\n");
        $this->addPrintedIndexRoute();

        $page = $this->get('blog/first-post');
        $page->assertOK();
        $page->assertSee('<h1>First post</h1>');
        $page->assertSee('Where it starts');
        $page->assertSee('rel="canonical" href="' . base_url('/blog/first-post') . '"');

        $this->assertSame(['/blog/second-post', '/blog/first-post'], $this->listed($this->visit('blog')));
    }

    /**
     * Adds the index route that the command tells the developer to add, with a page size of one so that the
     * fixture's two published entries make two pages.
     */
    private function addPrintedIndexRoute(): void
    {
        $this->assertSame(1, preg_match('/^\$routes->get\((?:[^\n]*\);|.*?^}\);)$/ms', $this->getStreamFilterBuffer(), $route), 'The command printed no index route.');

        eval('$routes = service(\'routes\');' . str_replace('paginate(10)', 'paginate(1)', $route[0]));
    }

    /**
     * A GET like a browser's. The pager reads the page from the superglobals, which the feature test leaves empty.
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
     * The URIs of the entries a page links to, in order.
     *
     * @return list<string>
     */
    private function listed(TestResponse $page): array
    {
        preg_match_all('#<a href="(/blog/[^"]+)"#', $page->getBody(), $matches);

        return $matches[1];
    }

    /**
     * Routes the blog, with its post blueprint, through the fallback route.
     */
    private function serveBlog(string $config): void
    {
        $this->reindexBlogWith("route: /blog/{slug}\nblueprints:\n  - post\n{$config}");
        config('Content')->autoRoute = true;
        $this->resetRouting();
    }

    private function resetRouting(): void
    {
        Services::resetSingle('routes');
        Services::resetSingle('router');
    }
}
