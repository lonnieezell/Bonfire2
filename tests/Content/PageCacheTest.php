<?php

namespace Tests\Content;

use Bonfire\Content\Collection;
use Bonfire\Content\ContentException;
use Bonfire\Content\ContentFiles;
use Bonfire\Content\EntryStore;
use Bonfire\Content\Indexer;
use Bonfire\Content\Models\ContentEntryModel;
use Bonfire\Content\Structure;
use Bonfire\View\Theme;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\I18n\Time;
use Config\Services;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Yaml\Yaml;
use Tests\Support\Content\BlogController;
use Tests\Support\Content\ContentTestCase;
use Tests\Support\Content\ExpiringCache;
use Tests\Support\Content\PageCacheController;

/**
 * A collection's `cache` option: guests get its content-routed pages from the cache until they expire or
 * anything in Content changes. A page is stale when it still shows what the index held when it was cached,
 * so these tests change the index row by hand, which clears nothing.
 *
 * @internal
 */
final class PageCacheTest extends ContentTestCase
{
    private const FIRST  = '11111111-1111-4111-8111-111111111111';
    private const SECOND = '22222222-2222-4222-8222-222222222222';
    private const HOME   = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa1';
    private const ABOUT  = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa2';
    private const TEAM   = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa3';

    protected function setUp(): void
    {
        parent::setUp();

        Time::setTestNow('2026-06-01 12:00:00');
        BlogController::$calls = 0;

        config('Themes')->collections = [SUPPORTPATH . 'Content/theme/'];
        self::setPrivateProperty(Theme::class, 'themeInfo', []);
        helper('auth');
        setting('Site.siteOnline', true);

        foreach (['auth', 'bonfire', 'menus', 'widgets'] as $service) {
            Services::resetSingle($service);
        }

        Services::injectMock('cache', new ExpiringCache());
        $this->resetRouting();
    }

    protected function tearDown(): void
    {
        Time::setTestNow();

        self::setPrivateProperty(Theme::class, 'themeInfo', []);
        $this->resetRouting();

        foreach (['auth', 'bonfire', 'menus', 'widgets', 'cache'] as $service) {
            Services::resetSingle($service);
        }

        parent::tearDown();
    }

    // Which requests are cached

    public function testARepeatGuestRequestComesFromTheCache(): void
    {
        $this->serveBlog(60);

        $this->get('blog/second-post')->assertSee('From the theme: Second post in Blog');
        $this->tamperWith(self::SECOND);

        $this->get('blog/second-post')->assertSee('From the theme: Second post in Blog');
    }

    public function testACollectionWithoutCacheIsNotCached(): void
    {
        $this->serveBlog(null);

        $this->get('blog/second-post')->assertSee('Second post');
        $this->tamperWith(self::SECOND);

        $this->get('blog/second-post')->assertSee('Tampered');
    }

    public function testEachUriHasItsOwnPage(): void
    {
        $this->serveBlog(60);

        $this->get('blog/first-post')->assertSee('First post');

        $second = $this->get('blog/second-post');

        $second->assertSee('Second post');
        $second->assertDontSee('First post');
    }

    public function testAControllersResponseIsCachedWithItsContentType(): void
    {
        $this->serveBlog(60, 'controller: ' . PageCacheController::class . '::show');
        PageCacheController::$calls = 0;

        $first  = $this->get('blog/first-post');
        $second = $this->get('blog/first-post');

        $this->assertSame(1, PageCacheController::$calls);
        $this->assertSame($first->getBody(), $second->getBody());
        $this->assertStringContainsString('application/json', $second->response()->getHeaderLine('Content-Type'));
        $second->assertOK();
    }

    public function testAControllerResponseThatIsNotA200IsNotCached(): void
    {
        // BlogController answers with a 201.
        $this->serveBlog(60, 'controller: ' . BlogController::class . '::show');

        $this->get('blog/first-post')->assertStatus(201);
        $this->get('blog/first-post')->assertStatus(201);

        $this->assertSame(2, BlogController::$calls);
    }

    public function testALoggedInUserAlwaysGetsAFreshPageAndLeavesNothingBehind(): void
    {
        $this->serveBlog(60);
        $user = $this->createUser();

        $this->actingAs($user)->get('blog/second-post')->assertSee('Second post');
        $this->tamperWith(self::SECOND);

        $this->actingAs($user)->get('blog/second-post')->assertSee('Tampered');
        // Nothing the user's requests rendered was stored for guests.
        $this->get('blog/second-post')->assertSee('Tampered');
    }

    public function testALoggedInUserDoesntGetAPageACachedGuestHas(): void
    {
        $this->serveBlog(60);

        $this->get('blog/second-post')->assertSee('Second post');
        $this->tamperWith(self::SECOND);

        $this->actingAs($this->createUser())->get('blog/second-post')->assertSee('Tampered');
    }

    public function testARequestWithAQueryStringIsNotCached(): void
    {
        $this->serveBlog(60);

        $this->get('blog/second-post?page=2')->assertSee('Second post');
        $this->tamperWith(self::SECOND);

        $this->get('blog/second-post?page=2')->assertSee('Tampered');
        $this->get('blog/second-post')->assertSee('Tampered');
    }

    public function testAnAppRouteIsNotCached(): void
    {
        $this->serveBlog(60);
        $calls = 0;

        service('routes')->get('blog/second-post', static function () use (&$calls): string {
            $calls++;

            return 'app route';
        });

        $this->get('blog/second-post')->assertSee('app route');
        $this->get('blog/second-post')->assertSee('app route');

        $this->assertSame(2, $calls);
    }

    public function testA404IsNotCached(): void
    {
        $this->serveBlog(60);

        $this->assertNotFound('blog/nothing-here');
        // The page exists after the next reindex, and the earlier 404 must not stand in the way.
        $this->writeFile('collections/blog/2026-04-01.nothing-here.md', "---\nid: 55555555-5555-4555-8555-555555555555\ntitle: Nothing here\n---\nNow it is.\n");
        $this->assertSame([], (new Indexer($this->site))->reindex()->errors);

        $this->get('blog/nothing-here')->assertSee('Nothing here');
    }

    public function testADraftIsStill404AfterItsPageWasCachedAsPublished(): void
    {
        $this->serveBlog(60);

        $this->get('blog/second-post')->assertSee('Second post');
        $this->reindexAfterEditing("---\nid: " . self::SECOND . "\ntitle: Second post\nstatus: draft\n---\nBody.\n");

        $this->assertNotFound('blog/second-post');
    }

    public function testThePreviewIsNeverCached(): void
    {
        $this->serveBlog(60);
        $user = $this->createUser();
        $user->addGroup('superadmin');

        // The routes are reset for every test, so route_to() doesn't know this one yet.
        $url     = ADMIN_AREA . '/content/preview';
        $headers = ['HX-Request' => 'true'];
        $first   = $this->actingAs($user)->withHeaders($headers)->post($url, ['markdown' => 'Some *words*']);
        $second  = $this->actingAs($user)->withHeaders($headers)->post($url, ['markdown' => 'Other **words**']);

        $first->assertSee('<em>words</em>');
        $second->assertSee('<strong>words</strong>');
        $pages = array_filter(cache()->getCacheInfo(), static fn (string $key): bool => str_starts_with($key, 'content_page_') && $key !== 'content_page_generation');

        $this->assertSame([], array_values($pages));
    }

    public function testACachedPageExpiresAfterTheCollectionsCacheSeconds(): void
    {
        $this->serveBlog(3600);

        $this->get('blog/second-post')->assertSee('Second post');
        $this->tamperWith(self::SECOND);

        Time::setTestNow('2026-06-01 12:59:59');
        $this->get('blog/second-post')->assertSee('From the theme: Second post');

        Time::setTestNow('2026-06-01 13:00:00');
        $this->get('blog/second-post')->assertSee('From the theme: Tampered');
    }

    public function testEachCollectionExpiresItsPagesAfterItsOwnSeconds(): void
    {
        $this->serveBlog(60);
        $this->writePages(7200);

        $this->get('blog/second-post')->assertSee('Second post');
        $this->get('about')->assertSee('About');
        $this->tamperWith(self::SECOND);
        $this->tamperWith(self::ABOUT);

        Time::setTestNow('2026-06-01 12:02:00');

        $this->get('blog/second-post')->assertSee('Tampered');
        $this->get('about')->assertSee('About');
    }

    // Config

    #[DataProvider('provideCacheMustBeAWholeNumberOfSeconds')]
    public function testCacheMustBeAWholeNumberOfSeconds(string $line): void
    {
        $this->writeFile('collections/blog.yaml', "title: Blog\n{$line}\n");

        $this->expectException(ContentException::class);
        $this->expectExceptionMessage('`cache` must be a whole number of seconds');

        Collection::load($this->site, 'blog');
    }

    public static function provideCacheMustBeAWholeNumberOfSeconds(): iterable
    {
        yield 'zero' => ['cache: 0'];

        yield 'negative' => ['cache: -5'];

        yield 'text' => ['cache: an hour'];

        yield 'a fraction' => ['cache: 1.5'];

        yield 'a boolean' => ['cache: true'];
    }

    public function testCacheIsOffByDefault(): void
    {
        $this->writeFile('collections/blog.yaml', "title: Blog\n");

        $this->assertNull(Collection::load($this->site, 'blog')->cache);
    }

    public function testCacheIsReadFromTheConfig(): void
    {
        $this->writeFile('collections/blog.yaml', "title: Blog\ncache: 3600\n");

        $this->assertSame(3600, Collection::load($this->site, 'blog')->cache);
    }

    // What clears it

    public function testSavingANewEntryClearsTheCache(): void
    {
        $this->serveBlog(60);

        $this->assertClears(fn () => $this->store()->create($this->blog(), 'brand-new', ['title' => 'Brand new'], 'Body.', '2026-05-01'));
    }

    public function testSavingAnEntryClearsTheCache(): void
    {
        $this->serveBlog(60);

        $this->assertClears(function (): void {
            $current = $this->store()->read($this->blog(), self::FIRST);

            $this->store()->update($this->blog(), $current, ['id' => self::FIRST, 'title' => 'Renamed'], 'Body.', hash('sha256', (string) file_get_contents($this->site . $current['path'])));
        });
    }

    public function testTrashingAnEntryClearsTheCache(): void
    {
        $this->serveBlog(60);

        $this->assertClears(fn () => $this->store()->trash($this->blog(), self::FIRST, 0));
    }

    public function testRestoringAnEntryClearsTheCache(): void
    {
        $this->serveBlog(60);
        $this->store()->trash($this->blog(), self::FIRST, 0);

        $this->assertClears(fn () => $this->store()->restore(self::FIRST));
    }

    public function testPurgingAnEntryClearsTheCache(): void
    {
        $this->serveBlog(60);
        $this->store()->trash($this->blog(), self::FIRST, 0);

        $this->assertClears(fn () => $this->store()->purge(self::FIRST));
    }

    public function testMovingAPageInTheTreeClearsTheCache(): void
    {
        $this->serveBlog(null);
        $this->writePages(60);

        $this->get('about')->assertSee('About');
        $this->get('about/team')->assertSee('Team');

        $hash = Structure::hash((string) file_get_contents($this->site . 'trees/collections/pages.yaml'));
        $this->store()->saveTree(Collection::load($this->site, 'pages'), Structure::parse(Yaml::dump([['id' => self::HOME], ['id' => self::ABOUT], ['id' => self::TEAM]], 10, 2)), $hash);

        // Team's old URI is gone, so a page cached there must be too.
        $this->assertNotFound('about/team');
        $this->get('team')->assertSee('Team');
    }

    public function testASaveThatIsRefusedStillClearsTheCache(): void
    {
        $this->serveBlog(60);

        $this->assertClears(function (): void {
            try {
                (new ContentFiles($this->site))->locked('blog', static function (): void {
                    throw new ContentException('Half done.');
                });
            } catch (ContentException) {
                // The changes before it threw may have been written.
            }
        });
    }

    public function testAReindexClearsTheCache(): void
    {
        $this->serveBlog(60);

        $this->assertClears(static fn () => (new Indexer(config('Content')->contentPath))->reindex());
    }

    public function testTheReindexCommandClearsTheCache(): void
    {
        $this->serveBlog(60);

        $this->assertClears(static fn () => command('content:reindex'));
    }

    public function testAReindexThatFailsLeavesTheCacheAlone(): void
    {
        $this->serveBlog(60);

        $this->get('blog/second-post')->assertSee('Second post');
        $result = (new Indexer($this->site . 'not-mounted/'))->reindex();
        $this->tamperWith(self::SECOND);

        $this->assertNotNull($result->failure);
        $this->get('blog/second-post')->assertSee('From the theme: Second post');
    }

    /**
     * Caches a page, makes the change, then changes what the index holds for that page. The page shows the second
     * change only if the first cleared the cache.
     *
     * @param callable(): mixed $change
     */
    private function assertClears(callable $change): void
    {
        $this->get('blog/second-post')->assertSee('Second post');

        $change();
        $this->tamperWith(self::SECOND);

        $this->get('blog/second-post')->assertSee('Tampered');
    }

    private function tamperWith(string $id): void
    {
        (new ContentEntryModel())->update($id, ['title' => 'Tampered']);
    }

    private function reindexAfterEditing(string $contents): void
    {
        $this->writeFile('collections/blog/2026-02-15.second-post.md', $contents);
        (new Indexer($this->site))->reindex();
    }

    private function store(): EntryStore
    {
        return new EntryStore($this->site);
    }

    private function blog(): Collection
    {
        return Collection::load($this->site, 'blog');
    }

    /**
     * Serves the blog collection with this `cache`, and these config lines.
     */
    private function serveBlog(?int $cache, string $config = "template: blog/show\n"): void
    {
        $this->reindexBlogWith("route: /blog/{slug}\n{$config}\n" . $this->cacheLine($cache));

        config('Content')->autoRoute = true;
    }

    private function cacheLine(?int $cache): string
    {
        return $cache === null ? '' : "cache: {$cache}\n";
    }

    /**
     * A structured pages collection: home, then about with team below it.
     */
    private function writePages(?int $cache): void
    {
        $this->writeFile('collections/pages.yaml', "title: Pages\nstructure: true\nroute: /{slug}\ntemplate: blog/show\n" . $this->cacheLine($cache));
        mkdir($this->site . 'collections/pages');
        mkdir($this->site . 'trees/collections', 0775, true);

        foreach ([self::HOME => 'home', self::ABOUT => 'about', self::TEAM => 'team'] as $id => $slug) {
            $this->writeFile("collections/pages/{$slug}.md", "---\nid: {$id}\ntitle: " . ucfirst($slug) . "\n---\nBody of {$slug}.\n");
        }

        $this->writeFile('trees/collections/pages.yaml', Yaml::dump([['id' => self::HOME], ['id' => self::ABOUT, 'children' => [['id' => self::TEAM]]]], 10, 2));
        (new Indexer($this->site))->reindex();
        $this->resetRouting();
    }

    private function assertNotFound(string $uri): void
    {
        try {
            $this->get($uri);
        } catch (PageNotFoundException) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->fail("{$uri} was served.");
    }

    private function resetRouting(): void
    {
        Services::resetSingle('routes');
        Services::resetSingle('router');
    }
}
