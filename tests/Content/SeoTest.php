<?php

namespace Tests\Content;

use Bonfire\Content\Blueprint;
use Bonfire\Content\ContentException;
use Bonfire\Content\Entry;
use Bonfire\Content\Indexer;
use Bonfire\View\Theme;
use CodeIgniter\I18n\Time;
use Config\Services;
use Tests\Support\Content\ContentTestCase;
use Tests\Support\Content\SeoController;

/**
 * @internal
 */
final class SeoTest extends ContentTestCase
{
    private const FIRST = '11111111-1111-4111-8111-111111111111';

    protected function setUp(): void
    {
        parent::setUp();

        Time::setTestNow('2026-06-01 12:00:00');

        config('Themes')->collections = [SUPPORTPATH . 'Content/theme/'];
        self::setPrivateProperty(Theme::class, 'themeInfo', []);

        Services::resetSingle('viewMeta');
        $this->resetRouting();
    }

    protected function tearDown(): void
    {
        Time::setTestNow();

        self::setPrivateProperty(Theme::class, 'themeInfo', []);
        Services::resetSingle('viewMeta');
        $this->resetRouting();

        parent::tearDown();

        // With the config back to its defaults, so that no fallback route is left in them: a later test may
        // call route_to() before it makes a request, and needs the routes to be loaded.
        service('routes')->loadRoutes();
    }

    public function testSeoReadsTheFieldsTheBlueprintMaps(): void
    {
        $this->mapSeo("seo:\n  title: tagline\n  description: summary\n");
        $this->writeEntry("tagline: Mapped title\nsummary: Mapped description\n", "Body paragraph.\n");

        $seo = $this->first()->seo();

        $this->assertSame('Mapped title', $seo['title']);
        $this->assertSame('Mapped description', $seo['description']);
        $this->assertSame('Mapped title', $seo['og']['og:title']);
        $this->assertSame('Mapped description', $seo['og']['og:description']);
    }

    public function testWithNoMappingTheTitleAndTheFirstParagraphAsPlainTextAreUsed(): void
    {
        $this->reindexBlogWith("route: /blog/{slug}\n");
        $this->writeEntry('', "The *first* [paragraph](https://example.com) has <b>markup</b> &amp; more.\nIt wraps.\n\nThe second paragraph.\n");
        (new Indexer($this->site))->reindex();

        $seo = $this->first()->seo();

        $this->assertSame('First post', $seo['title']);
        $this->assertSame('The first paragraph has markup & more. It wraps.', $seo['description']);
    }

    public function testAParagraphWithNoTextIsSkippedAndNoParagraphLeavesTheDescriptionOut(): void
    {
        $this->reindexBlogWith("route: /blog/{slug}\n");
        $this->writeEntry('', "![A picture](/a.png)\n\n<p class=\"lead\">The real <em>text</em>.</p>\n");

        $this->assertSame('The real text.', $this->first()->seo()['description']);

        $this->writeEntry('', "![A picture](/a.png)\n");

        $seo = $this->first()->seo();
        $this->assertSame('', $seo['description']);
        $this->assertArrayNotHasKey('og:description', $seo['og']);

        content_seo($this->first());
        $this->assertStringNotContainsString('description', (string) service('viewMeta')->render('meta'));
    }

    public function testABlankMappedFieldFallsBackToTheDefault(): void
    {
        $this->mapSeo("seo:\n  title: tagline\n  description: summary\n");
        $this->writeEntry("summary: ''\n", "Fallback paragraph.\n");

        $seo = $this->first()->seo();

        $this->assertSame('First post', $seo['title']);
        $this->assertSame('Fallback paragraph.', $seo['description']);
    }

    public function testAMappedToggleIsNotATitle(): void
    {
        $this->mapSeo("seo:\n  title: featured\n");
        $this->writeEntry("featured: true\n", "Body.\n");

        $this->assertSame('First post', $this->first()->seo()['title']);
    }

    public function testAnEntryWhoseBlueprintIsGoneUsesTheDefaults(): void
    {
        $this->reindexBlogWith("route: /blog/{slug}\nblueprints:\n  - ghost\n");

        $this->assertSame('First post', $this->first()->seo()['title']);
    }

    public function testTheCanonicalUrlIsTheEntrysUri(): void
    {
        $this->reindexBlogWith("route: /blog/{slug}\n");

        $seo = $this->first()->seo();

        $this->assertSame(base_url('/blog/first-post'), $seo['canonical']);
        $this->assertSame(base_url('/blog/first-post'), $seo['og']['og:url']);
    }

    public function testAnEntryWithoutAUriHasNoCanonicalUrl(): void
    {
        $this->reindexBlogWith('');

        $seo = $this->first()->seo();

        $this->assertNull($seo['canonical']);
        $this->assertArrayNotHasKey('og:url', $seo['og']);
    }

    public function testABlueprintMappingAFieldThatDoesntExistIsInvalid(): void
    {
        $this->mapSeo("seo:\n  title: nope\n");

        $this->expectException(ContentException::class);
        $this->expectExceptionMessage('`nope`');

        Blueprint::load($this->blueprints, 'blog', 'post');
    }

    public function testABlueprintMappingAnUnknownSeoValueIsInvalid(): void
    {
        $this->mapSeo("seo:\n  keywords: tagline\n");

        $this->expectException(ContentException::class);

        Blueprint::load($this->blueprints, 'blog', 'post');
    }

    public function testContentSeoPutsTheValuesInTheHeadEscaped(): void
    {
        $this->mapSeo("seo:\n  title: tagline\n  description: summary\n");
        $this->writeEntry("tagline: 'A \"quoted\" <b>title</b> & more'\nsummary: 'Say \"hi\" <script>x</script>'\n", "Body.\n");

        $head = $this->headFor($this->first());

        $this->assertStringContainsString('<title>A &quot;quoted&quot; &lt;b&gt;title&lt;/b&gt; &amp; more</title>', $head);
        $this->assertStringContainsString('name="description" content="Say &quot;hi&quot; &lt;script&gt;x&lt;/script&gt;"', $head);
        $this->assertStringContainsString('property="og:title" content="A &quot;quoted&quot; &lt;b&gt;title&lt;/b&gt; &amp; more"', $head);
        $this->assertStringContainsString('rel="canonical" href="' . base_url('/blog/first-post') . '"', $head);
        $this->assertStringContainsString('property="og:url" content="' . base_url('/blog/first-post') . '"', $head);
        $this->assertStringNotContainsString('<script>x', $head);
        $this->assertStringNotContainsString('<b>title', $head);
    }

    public function testATemplateRenderedEntryGetsNoTagsByDefault(): void
    {
        $this->serveBlog('template: blog/seo');

        $this->assertFalse(config('Content')->autoSeo);

        $body = $this->get('blog/first-post')->getBody();

        $this->assertStringNotContainsString('name="description"', $body);
        $this->assertStringNotContainsString('rel="canonical"', $body);
        $this->assertStringNotContainsString('property="og:', $body);
    }

    public function testATemplateRenderedEntryGetsTheTagsWithAutoSeoOn(): void
    {
        $this->serveBlog('template: blog/seo');
        config('Content')->autoSeo = true;

        $body = $this->get('blog/first-post')->getBody();

        $this->assertStringContainsString('<title>First post</title>', $body);
        $this->assertStringContainsString('name="description" content="The first post."', $body);
        $this->assertStringContainsString('rel="canonical" href="' . base_url('/blog/first-post') . '"', $body);
    }

    public function testAControllerHandOffIsLeftToTheDeveloperEvenWithAutoSeoOn(): void
    {
        $this->serveBlog('controller: ' . SeoController::class . '::show');
        config('Content')->autoSeo = true;

        // The controller calls content_seo() once itself, so there is one set of tags and no more.
        $body = $this->get('blog/first-post')->getBody();

        $this->assertSame(1, substr_count($body, 'name="description"'));
        $this->assertStringContainsString('rel="canonical"', $body);
    }

    public function testAControllerHandOffGetsNoTagsUnlessItAsksForThem(): void
    {
        $this->serveBlog('controller: ' . SeoController::class . '::bare');
        config('Content')->autoSeo = true;

        $body = $this->get('blog/first-post')->getBody();

        $this->assertStringContainsString('<body>First post</body>', $body);
        $this->assertStringNotContainsString('name="description"', $body);
        $this->assertStringNotContainsString('rel="canonical"', $body);
    }

    public function testAHandWrittenRouteUsingFindByUriCanAskForTheTags(): void
    {
        $this->reindexBlogWith("route: /blog/{slug}\n");

        $entry = content()->findByUri('/blog/first-post');
        content_seo($entry);

        $this->assertStringContainsString('name="description" content="The first post."', (string) service('viewMeta')->render('meta'));
        $this->assertSame('First post', service('viewMeta')->title());
        $this->assertStringContainsString('rel="canonical" href="' . base_url('/blog/first-post') . '"', (string) service('viewMeta')->render('link'));
    }

    private function first(): Entry
    {
        return content()->find(self::FIRST);
    }

    /**
     * The head that the SeoController's page renders for the entry.
     */
    private function headFor(Entry $entry): string
    {
        return (new SeoController())->show($entry);
    }

    /**
     * Gives the post blueprint this `seo` mapping, and reindexes the blog with a route and the blueprint.
     */
    private function mapSeo(string $mapping): void
    {
        file_put_contents($this->blueprints . 'collections/blog/post.yaml', $mapping, FILE_APPEND);
        $this->reindexBlogWith("route: /blog/{slug}\nblueprints:\n  - post\n");
    }

    /**
     * Rewrites the first post with these extra front matter lines and this body, then reindexes.
     */
    private function writeEntry(string $frontMatter, string $body): void
    {
        $this->writeFile('collections/blog/2026-01-10.first-post.md', "---\nid: " . self::FIRST . "\ntitle: First post\n{$frontMatter}---\n{$body}");
        (new Indexer($this->site))->reindex();
    }

    /**
     * Serves the blog at /blog/{slug} with these config lines.
     */
    private function serveBlog(string $config): void
    {
        $this->reindexBlogWith("route: /blog/{slug}\n{$config}\n");

        config('Content')->autoRoute = true;
    }

    private function resetRouting(): void
    {
        Services::resetSingle('routes');
        Services::resetSingle('router');
    }
}
