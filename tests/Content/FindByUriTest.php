<?php

namespace Tests\Content;

use CodeIgniter\I18n\Time;
use Tests\Support\Content\ContentTestCase;

/**
 * @internal
 */
final class FindByUriTest extends ContentTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Time::setTestNow('2026-06-01 12:00:00');
    }

    protected function tearDown(): void
    {
        Time::setTestNow();

        parent::tearDown();
    }

    public function testFindsAPublishedEntryByItsUri(): void
    {
        $this->reindexBlogWith("route: /blog/{slug}\n");

        $entry = content()->findByUri('/blog/first-post');

        $this->assertSame('first-post', $entry->slug);
        $this->assertSame('/blog/first-post', $entry->uri);
    }

    public function testTheLeadingAndTrailingSlashesDontMatter(): void
    {
        $this->reindexBlogWith("route: /blog/{slug}\n");

        $this->assertSame('first-post', content()->findByUri('blog/first-post/')?->slug);
    }

    public function testRouteMayOmitItsLeadingSlash(): void
    {
        $this->reindexBlogWith("route: blog/{slug}\n");

        $this->assertSame('/blog/first-post', content()->findByUri('/blog/first-post')?->uri);
    }

    public function testFindsNothingForADraftAFutureEntryOrAnUnknownUri(): void
    {
        $this->reindexBlogWith("route: /blog/{slug}\n");

        $this->assertNull(content()->findByUri('/blog/draft-post'));
        $this->assertNull(content()->findByUri('/blog/future-post'));
        $this->assertNull(content()->findByUri('/blog/nothing-here'));
    }

    public function testACollectionWithNoRouteGetsNoUrls(): void
    {
        $this->reindexBlogWith('');

        $this->assertNull(content()->find('11111111-1111-4111-8111-111111111111')?->uri);
        $this->assertNull(content()->findByUri('/blog/first-post'));
    }

    public function testRemovingTheRouteClearsTheUris(): void
    {
        $this->reindexBlogWith("route: /blog/{slug}\n");
        $this->reindexBlogWith('');

        $this->assertNull(content()->findByUri('/blog/first-post'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function provideAnInvalidRoutingConfigIsReported(): iterable
    {
        return [
            'template and controller'    => ["route: /blog/{slug}\ntemplate: blog/show\ncontroller: App\\Controllers\\Blog::show\n"],
            'route is not a string'      => ["route: [a, b]\n"],
            'route has no slug'          => ["route: /blog\n"],
            'route has another variable' => ["route: /blog/{year}/{slug}\n"],
            'template is not a string'   => ["route: /blog/{slug}\ntemplate: 12\n"],
            'controller has no method'   => ["route: /blog/{slug}\ncontroller: App\\Controllers\\Blog\n"],
        ];
    }

    public function testTheSecondEntryWithAUriIsReported(): void
    {
        $this->writeFile(
            'collections/blog/2026-04-01.first-post.md',
            "---\nid: 88888888-8888-4888-8888-888888888888\n---\nSame slug, later date.\n",
        );

        $result = $this->reindexBlogWith("route: /blog/{slug}\n");

        $this->assertArrayHasKey('collections/blog/2026-04-01.first-post.md', $result->errors);
        $this->assertSame('11111111-1111-4111-8111-111111111111', content()->findByUri('/blog/first-post')?->id);
    }

    public function testUrisThatDifferOnlyByCaseAreReported(): void
    {
        $this->writeFile('collections/blog/2026-04-01.First-Post.md', "---\nid: 88888888-8888-4888-8888-888888888888\n---\nCase.\n");

        $result = $this->reindexBlogWith("route: /blog/{slug}\n");

        $this->assertArrayHasKey('collections/blog/2026-04-01.First-Post.md', $result->errors);
    }

    public function testAUriTooLongForTheIndexIsReported(): void
    {
        $result = $this->reindexBlogWith('route: /' . str_repeat('a', 255) . "/{slug}\n");

        $this->assertArrayHasKey('collections/blog/2026-01-10.first-post.md', $result->errors);
    }
}
