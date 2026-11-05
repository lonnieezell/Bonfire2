<?php

namespace Tests\Content;

use Bonfire\Content\Models\ContentEntryModel;
use Bonfire\Content\PreviewLink;
use Bonfire\Users\User;
use Bonfire\View\Theme;
use CodeIgniter\Cache\ResponseCache;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Content\BlogController;
use Tests\Support\Content\ContentTestCase;

/**
 * @internal
 */
final class DraftPreviewTest extends ContentTestCase
{
    private const FIRST  = '11111111-1111-4111-8111-111111111111';
    private const DRAFT  = '33333333-3333-4333-8333-333333333333';
    private const FUTURE = '44444444-4444-4444-8444-444444444444';

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        Time::setTestNow('2026-06-01 12:00:00');
        BlogController::$calls = 0;
        setting('Site.siteOnline', true);
        config('Encryption')->key = 'a-key-for-the-tests';
        // Before the real themes, so its App theme is the one found, and the Admin theme is still there for the admin screens.
        array_unshift(config('Themes')->collections, SUPPORTPATH . 'Content/theme/');
        self::setPrivateProperty(Theme::class, 'themeInfo', []);

        foreach (['auth', 'bonfire', 'menus', 'widgets'] as $service) {
            Services::resetSingle($service);
        }

        $this->reloadRoutes();

        $this->editor = $this->createUser();
        $this->editor->addPermission('admin.access', 'content.access', 'content.edit');
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        BlogController::$cache = false;
        self::setPrivateProperty(Theme::class, 'themeInfo', []);

        foreach (['auth', 'bonfire', 'menus', 'widgets', 'responsecache', 'routes', 'router'] as $service) {
            Services::resetSingle($service);
        }

        parent::tearDown();
    }

    // Making the link

    public function testAnEditorSeesThePreviewActionAndGetsAWorkingLink(): void
    {
        $this->reindexBlogWith("template: blog/show\nblueprints:\n  - post\n");

        $form = $this->actingAs($this->editor)->get(route_to('content-edit', 'blog', self::DRAFT));
        $this->assertStringContainsString('href="' . url_to('content-preview-link', 'blog', self::DRAFT) . '" class="btn btn-outline-secondary" data-preview-link', $form->getBody());

        $page = $this->actingAs($this->editor)->get(route_to('content-preview-link', 'blog', self::DRAFT));
        $page->assertOK();

        $link = $this->linkIn($page);
        $this->assertStringStartsWith('content-preview/' . self::DRAFT . '?expires=', $link);

        auth()->logout();
        $this->assertFalse(auth()->loggedIn());

        $this->get($link)->assertSee('From the theme: Draft post in Blog');
    }

    public function testWithoutContentEditThereIsNoPreviewActionAndNoLink(): void
    {
        $this->reindexBlogWith("route: /blog/{slug}\ntemplate: blog/show\n");

        $user = $this->createUser();
        $user->addPermission('admin.access', 'content.access');

        $this->actingAs($user)->get(route_to('content-edit', 'blog', self::DRAFT))->assertRedirectTo(site_url(ADMIN_AREA));

        $response = $this->actingAs($user)->get(route_to('content-preview-link', 'blog', self::DRAFT));
        $response->assertRedirectTo(site_url(ADMIN_AREA));
        $response->assertSessionHas('error', lang('Bonfire.notAuthorized'));
    }

    public function testACollectionWithNeitherATemplateNorAControllerOffersNoPreview(): void
    {
        $this->reindexBlogWith("route: /blog/{slug}\nblueprints:\n  - post\n");

        $form = $this->actingAs($this->editor)->get(route_to('content-edit', 'blog', self::DRAFT));
        $form->assertOK();
        $this->assertStringNotContainsString('data-preview-link', $form->getBody());

        $this->expectException(PageNotFoundException::class);

        $this->actingAs($this->editor)->get(route_to('content-preview-link', 'blog', self::DRAFT));
    }

    public function testALinkForACollectionWithNeitherIsA404(): void
    {
        $this->reindexBlogWith("route: /blog/{slug}\n");

        // Signed as if the collection had a template when the link was made.
        $this->assertNotFound($this->linkFor(self::DRAFT));
    }

    public function testWithoutAnEncryptionKeyTheEditorIsToldAndNoLinkWorks(): void
    {
        $this->reindexBlogWith("template: blog/show\n");
        $link = $this->linkFor(self::DRAFT);

        config('Encryption')->key = '';

        $response = $this->actingAs($this->editor)->get(route_to('content-preview-link', 'blog', self::DRAFT));
        $response->assertRedirectTo(route_to('content-list', 'blog'));
        $response->assertSessionHas('error');

        $this->assertNotFound($link);
    }

    public function testAnEntryWhoseFileIsGoneGivesAnErrorNotACrash(): void
    {
        $this->reindexBlogWith("template: blog/show\n");
        unlink($this->site . 'collections/blog/2026-03-01.draft-post.md');

        $response = $this->actingAs($this->editor)->get(route_to('content-preview-link', 'blog', self::DRAFT));

        $response->assertRedirectTo(route_to('content-list', 'blog'));
        $response->assertSessionHas('error');
    }

    public function testATrashedEntryHasNoPreviewLink(): void
    {
        $this->reindexBlogWith("template: blog/show\n");
        $link = $this->linkFor(self::DRAFT);

        (new ContentEntryModel())->delete(self::DRAFT);

        $this->assertNotFound($link);
    }

    // Opening the link

    public function testALinkRendersADraftAndAScheduledEntryThroughTheTemplateWithNoLogin(): void
    {
        $this->reindexBlogWith("template: blog/show\n");

        $this->get($this->linkFor(self::DRAFT))->assertSee('From the theme: Draft post in Blog');
        $this->get($this->linkFor(self::FUTURE))->assertSee('From the theme: Future post in Blog');
        $this->get($this->linkFor(self::FIRST))->assertSee('From the theme: First post in Blog');
    }

    public function testALinkHandsOffToTheCollectionsController(): void
    {
        $this->reindexBlogWith('controller: ' . BlogController::class . "::show\n");

        $result = $this->get($this->linkFor(self::DRAFT));

        $result->assertStatus(201);
        $result->assertJSONExact(['slug' => 'draft-post']);
        $this->assertSame(1, BlogController::$calls);
    }

    public function testALinkWorksUntilItExpires(): void
    {
        $this->reindexBlogWith("template: blog/show\n");
        $link = $this->linkFor(self::DRAFT);

        $expires = Time::now()->getTimestamp() + PreviewLink::LIFETIME;

        Time::setTestNow(Time::createFromTimestamp($expires));
        $this->get($link)->assertSee('Draft post');

        Time::setTestNow(Time::createFromTimestamp($expires + 1));
        $this->assertNotFound($link);
        $this->assertSame(0, BlogController::$calls);
    }

    public function testAChangedSignatureIsA404(): void
    {
        $this->reindexBlogWith("template: blog/show\n");
        $link = $this->linkFor(self::DRAFT);

        $this->assertNotFound(preg_replace('/signature=(.)/', 'signature=0$1', $link));
        $this->assertNotFound(preg_replace('/signature=[0-9a-f]+/', 'signature=', $link));
        $this->assertNotFound(preg_replace('/&?signature=[0-9a-f]+/', '', $link));
    }

    public function testAChangedExpiryIsA404(): void
    {
        $this->reindexBlogWith("template: blog/show\n");
        $link = $this->linkFor(self::DRAFT);

        $this->assertNotFound(preg_replace('/expires=\d+/', 'expires=' . (Time::now()->getTimestamp() + 10 * PreviewLink::LIFETIME), $link));
        $this->assertNotFound(preg_replace('/expires=\d+/', 'expires=abc', $link));
        $this->assertNotFound(preg_replace('/expires=\d+&?/', '', $link));
    }

    public function testALinkUsedForAnotherEntryIsA404(): void
    {
        $this->reindexBlogWith("template: blog/show\n");
        $link = $this->linkFor(self::DRAFT);

        $this->assertNotFound(str_replace(self::DRAFT, self::FUTURE, $link));
        $this->assertNotFound(str_replace(self::DRAFT, self::FIRST, $link));
    }

    public function testALinkForAnEntryThatDoesNotExistIsA404(): void
    {
        $this->reindexBlogWith("template: blog/show\n");

        $this->assertNotFound($this->linkFor('99999999-9999-4999-8999-999999999999'));
    }

    public function testALinkSignedWithAnotherKeyIsA404(): void
    {
        $this->reindexBlogWith("template: blog/show\n");
        $link = $this->linkFor(self::DRAFT);

        config('Encryption')->key = 'another-key';

        $this->assertNotFound($link);
    }

    // The response

    #[DataProvider('provideTheResponseIsNoindexAndNotCacheable')]
    public function testTheResponseIsNoindexAndNotCacheable(string $config): void
    {
        $this->reindexBlogWith($config);

        $result = $this->get($this->linkFor(self::DRAFT));

        $this->assertSame('noindex', $result->response()->getHeaderLine('X-Robots-Tag'));
        $this->assertStringContainsString('no-store', $result->response()->getHeaderLine('Cache-Control'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideTheResponseIsNoindexAndNotCacheable(): iterable
    {
        yield 'a template' => ["template: blog/show\n"];

        yield 'a controller' => ['controller: ' . BlogController::class . "::show\n"];
    }

    public function testAPreviewIsNotCachedEvenWhenTheControllerAsksForPageCaching(): void
    {
        $this->reindexBlogWith("route: /blog/{slug}\ncontroller: " . BlogController::class . "::show\n");
        config('Content')->autoRoute = true;
        BlogController::$cache       = true;
        $this->reloadRoutes();

        // Pages are cached in memory here, not in the writable folder.
        Services::injectMock('responsecache', new ResponseCache(config('Cache'), service('cache')));

        // The same controller is cached on the site, so the cache is working in this test.
        $this->get('blog/first-post');
        $this->get('blog/first-post');
        $this->assertSame(1, BlogController::$calls);

        BlogController::$calls = 0;
        $link                  = $this->linkFor(self::DRAFT);

        $this->get($link);
        $this->get($link);

        $this->assertSame(2, BlogController::$calls);
    }

    // The site

    public function testTheSiteStillTreatsDraftsAndScheduledEntriesAsMissing(): void
    {
        $this->reindexBlogWith("route: /blog/{slug}\ntemplate: blog/show\n");
        config('Content')->autoRoute = true;
        $this->reloadRoutes();

        $this->get($this->linkFor(self::DRAFT))->assertOK();

        $this->assertNotFound('blog/draft-post');
        $this->assertNotFound('blog/future-post');
    }

    /**
     * The routes are read once, so this makes them follow a config change. url_to() needs them loaded before the first request.
     */
    private function reloadRoutes(): void
    {
        Services::resetSingle('routes');
        Services::resetSingle('router');
        service('routes')->loadRoutes();
    }

    /**
     * The link the admin gives for the entry, made without logging anyone in.
     */
    private function linkFor(string $id): string
    {
        return $this->pathOf(PreviewLink::create($id, Time::now()->getTimestamp() + PreviewLink::LIFETIME));
    }

    private function linkIn(TestResponse $page): string
    {
        $this->assertSame(1, preg_match('/value="([^"]+)"[^>]*data-preview-url/', $page->getBody(), $matches), 'The page has no link.');

        return $this->pathOf(html_entity_decode($matches[1]));
    }

    /**
     * What the test client takes: the URL without the site's address.
     */
    private function pathOf(string $url): string
    {
        return ltrim((string) preg_replace('#^' . preg_quote(site_url(), '#') . '#', '', $url), '/');
    }

    private function assertNotFound(string $link): void
    {
        try {
            $this->get($link);
        } catch (PageNotFoundException) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->fail("{$link} was served.");
    }
}
