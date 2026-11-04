<?php

namespace Tests\Content;

use Bonfire\Content\Indexer;
use Bonfire\View\Theme;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\I18n\Time;
use Config\Services;
use Tests\Support\Content\BlogController;
use Tests\Support\Content\ContentTestCase;

/**
 * @internal
 */
final class FrontEndRoutingTest extends ContentTestCase
{
    private const APP_VIEW = APPPATH . 'Views/content_routing_probe.php';

    protected function setUp(): void
    {
        parent::setUp();

        Time::setTestNow('2026-06-01 12:00:00');
        BlogController::$calls = 0;

        config('Themes')->collections = [SUPPORTPATH . 'Content/theme/'];
        self::setPrivateProperty(Theme::class, 'themeInfo', []);

        $this->resetRouting();
    }

    protected function tearDown(): void
    {
        Time::setTestNow();

        if (is_file(self::APP_VIEW)) {
            unlink(self::APP_VIEW);
        }

        self::setPrivateProperty(Theme::class, 'themeInfo', []);
        $this->resetRouting();

        parent::tearDown();
    }

    public function testWithoutAutoRouteNoContentRouteExists(): void
    {
        $this->serveBlog();
        config('Content')->autoRoute = false;

        $this->assertNotFound('blog/first-post');
        $this->assertArrayNotHasKey('(.*)', service('routes')->getRoutes('GET'));
    }

    public function testAPublishedEntryRendersAtItsUri(): void
    {
        $this->serveBlog();

        $result = $this->get('blog/first-post');

        $result->assertOK();
        $result->assertSee('From the theme: First post in Blog');
        $result->assertSee('<em>post</em>');
    }

    public function testANestedUriOfAStructuredCollectionIsServed(): void
    {
        $team  = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa3';
        $about = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa2';

        mkdir($this->site . 'collections/pages');
        mkdir($this->site . 'trees/collections', 0775, true);
        $this->writeFile('collections/pages/about.md', "---\nid: {$about}\ntitle: About\n---\n");
        $this->writeFile('collections/pages/team.md', "---\nid: {$team}\ntitle: Team\n---\n");
        $this->writeFile('trees/collections/pages.yaml', "- id: {$about}\n  children:\n    - id: {$team}\n");
        $this->serveBlog("route: /blog/{slug}\ntemplate: blog/show\n");
        $this->writeFile('collections/pages.yaml', "title: Pages\nstructure: true\nroute: /{slug}\ncontroller: " . BlogController::class . "::show\n");
        (new Indexer($this->site))->reindex();
        $this->resetRouting();

        $result = $this->get('about/team');
        $result->assertStatus(201);
        $result->assertJSONExact(['slug' => 'team']);
        $this->assertNotFound('team');
    }

    public function testATemplateFallsBackToAppViews(): void
    {
        file_put_contents(self::APP_VIEW, 'From app views: <?= esc($entry->title) ?>');
        $this->serveBlog("route: /blog/{slug}\ntemplate: content_routing_probe\n");

        $this->get('blog/first-post')->assertSee('From app views: First post');
    }

    public function testATemplateIsFoundInAppViewsWithNoAppTheme(): void
    {
        config('Themes')->collections = [];
        self::setPrivateProperty(Theme::class, 'themeInfo', []);
        file_put_contents(self::APP_VIEW, 'From app views: <?= esc($entry->title) ?>');
        $this->serveBlog("route: /blog/{slug}\ntemplate: content_routing_probe\n");

        $this->get('blog/first-post')->assertSee('From app views: First post');
    }

    public function testADeletedCollectionConfigIsA404BeforeTheReindex(): void
    {
        $this->serveBlog();

        unlink($this->site . 'collections/blog.yaml');

        $this->assertNotFound('blog/first-post');
    }

    public function testABrokenCollectionConfigIsA404AndLoggedBeforeTheReindex(): void
    {
        $this->serveBlog();

        $this->writeFile('collections/blog.yaml', "route: [\n");

        $this->assertNotFound('blog/first-post');
        $this->assertLogContains('error', 'Content: collection blog');
    }

    public function testDraftsFutureEntriesAndUnknownUrisAre404s(): void
    {
        $this->serveBlog();

        $this->assertNotFound('blog/draft-post');
        $this->assertNotFound('blog/future-post');
        $this->assertNotFound('blog/nothing-here');
    }

    public function testAnAppRouteWinsOverTheContentRoute(): void
    {
        $this->serveBlog();

        // Before discovery, as the app's Routes.php is.
        service('routes')->get('blog/first-post', static fn (): string => 'app route');

        $this->get('blog/first-post')->assertSee('app route');
    }

    public function testAnAppRouteAddedForAnyVerbWinsOverTheContentRoute(): void
    {
        $this->serveBlog();

        service('routes')->add('blog/first-post', static fn (): string => 'app route');

        $this->get('blog/first-post')->assertSee('app route');
    }

    public function testAnAllNumericAppRouteStillWorks(): void
    {
        $this->serveBlog();

        service('routes')->get('2026', static fn (): string => 'numeric route');

        $this->get('2026')->assertSee('numeric route');
    }

    public function testTheContentRouteLoadsAfterEveryOtherRoute(): void
    {
        $this->serveBlog();

        $routes = array_keys(service('routes')->loadRoutes()->getRoutes('GET'));

        $this->assertSame('(.*)', end($routes));
    }

    public function testContentsRouteFileLoadsAfterThoseOfAllOtherModules(): void
    {
        // The system folder is searched last, whatever the order of the others, and adds no routes.
        $files = array_filter(
            service('locator')->search('Config/Routes.php'),
            static fn (string $file): bool => ! str_starts_with($file, realpath(SYSTEMPATH)),
        );

        $this->assertStringEndsWith('Content/Config/Routes.php', end($files));
    }

    public function testOnlyGetRequestsAreServed(): void
    {
        $this->serveBlog();

        $this->expectException(PageNotFoundException::class);

        $this->post('blog/first-post');
    }

    public function testRoutesInOtherModulesStillWork(): void
    {
        $this->serveBlog();

        $this->get('site-offline')->assertRedirect();
    }

    public function testACollectionWithNoRouteIsNotServed(): void
    {
        $this->serveBlog("template: blog/show\n");

        $this->assertNotFound('blog/first-post');
    }

    public function testARouteWithNothingToRenderItIsNotServed(): void
    {
        $this->serveBlog("route: /blog/{slug}\n");

        $this->assertNotFound('blog/first-post');
    }

    public function testAControllerGetsTheEntryAndItsReturnValueIsTheResponse(): void
    {
        $this->serveBlog("route: /blog/{slug}\ncontroller: " . BlogController::class . "::show\n");

        $result = $this->get('blog/first-post');

        $result->assertStatus(201);
        $result->assertJSONExact(['slug' => 'first-post']);
        $this->assertSame(1, BlogController::$calls);
    }

    public function testAControllerIsNotCalledForDraftsOrFutureEntries(): void
    {
        $this->serveBlog("route: /blog/{slug}\ncontroller: " . BlogController::class . "::show\n");

        $this->assertNotFound('blog/draft-post');
        $this->assertNotFound('blog/future-post');
        $this->assertSame(0, BlogController::$calls);
    }

    public function testTheStaleCheckDoesntRunOutsideDevelopment(): void
    {
        $this->serveBlog();

        $this->writeFile('collections/blog/2026-01-10.first-post.md', "---\nid: 11111111-1111-4111-8111-111111111111\ntitle: Edited\n---\nEdited.\n");

        $this->get('blog/first-post')->assertSee('First post');
    }

    /**
     * Serves the blog collection, with these config lines.
     */
    private function serveBlog(string $config = "route: /blog/{slug}\ntemplate: blog/show\n"): void
    {
        $this->reindexBlogWith($config);

        config('Content')->autoRoute = true;
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

    /**
     * The routes are read once per request, and these tests make several apps' worth.
     */
    private function resetRouting(): void
    {
        Services::resetSingle('routes');
        Services::resetSingle('router');
    }
}
