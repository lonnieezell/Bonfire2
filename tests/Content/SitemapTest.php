<?php

namespace Tests\Content;

use Bonfire\Content\Entry;
use Bonfire\Content\Indexer;
use Bonfire\Content\Models\ContentEntryModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\I18n\Time;
use Config\Services;
use Tests\Support\Content\ContentTestCase;

/**
 * @internal
 */
final class SitemapTest extends ContentTestCase
{
    private const FIRST_POST  = '11111111-1111-4111-8111-111111111111';
    private const ABOUT       = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa2';
    private const UNROUTED_ID = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';

    protected function setUp(): void
    {
        parent::setUp();

        Time::setTestNow('2026-06-01 12:00:00');
        $this->resetRouting();
    }

    protected function tearDown(): void
    {
        Time::setTestNow();

        parent::tearDown();

        // Leave the routes loaded, as other tests find them, now that the Content config is the default again.
        $this->resetRouting();
        service('routes')->loadRoutes();
    }

    public function testItIsAnXmlResponseOfTheUrlsetKind(): void
    {
        $this->reindexBlogWith("route: /blog/{slug}\n");

        $response = content()->sitemap();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringStartsWith('application/xml', $response->getHeaderLine('Content-Type'));

        $xml = simplexml_load_string((string) $response->getBody());

        $this->assertNotFalse($xml, 'The body is not well-formed XML.');
        $this->assertSame('urlset', $xml->getName());
        $this->assertSame('http://www.sitemaps.org/schemas/sitemap/0.9', $xml->getNamespaces()['']);
    }

    public function testItCoversAllCollections(): void
    {
        $this->reindexBlogWith("route: /blog/{slug}\n");
        $this->addPagesCollection();

        $this->assertSame(
            [site_url('about'), site_url('blog/first-post'), site_url('blog/second-post')],
            $this->locations(),
        );
    }

    public function testItLeavesOutDraftsScheduledAndTrashedEntries(): void
    {
        $this->reindexBlogWith("route: /blog/{slug}\n");

        (new ContentEntryModel())->delete(self::FIRST_POST);

        $locations = $this->locations();

        $this->assertSame([site_url('blog/second-post')], $locations);
        $this->assertNotContains(site_url('blog/draft-post'), $locations);
        $this->assertNotContains(site_url('blog/future-post'), $locations);
    }

    public function testAnEntryWithNoUriIsLeftOut(): void
    {
        $this->reindexBlogWith("route: /blog/{slug}\n");
        $this->addPagesCollection();
        $this->writeFile('collections/loose.yaml', "title: Loose\n");
        mkdir($this->site . 'collections/loose');
        $this->writeFile('collections/loose/unrouted.md', "---\nid: " . self::UNROUTED_ID . "\ntitle: Unrouted\n---\n");
        (new Indexer($this->site))->reindex();

        $this->assertInstanceOf(Entry::class, content()->find(self::UNROUTED_ID));
        $this->assertCount(3, $this->locations());
        $this->assertStringNotContainsString('unrouted', implode(' ', $this->locations()));
    }

    public function testLocationsAreAbsoluteEncodedAndEscaped(): void
    {
        $this->reindexBlogWith("route: /blog/{slug}\n");
        $this->writeFile("collections/blog/2026-01-11.caf\u{e9} & tea.md", "---\nid: dddddddd-dddd-4ddd-8ddd-dddddddddddd\ntitle: Cafe\n---\n");
        (new Indexer($this->site))->reindex();

        $body = (string) content()->sitemap()->getBody();

        $this->assertNotFalse(simplexml_load_string($body), 'The body is not well-formed XML.');
        $this->assertStringContainsString('<loc>' . rtrim(site_url(), '/') . '/blog/caf%C3%A9%20%26%20tea</loc>', $body);
    }

    public function testASiteWithNoUrlsGetsAnEmptyUrlset(): void
    {
        $xml = simplexml_load_string((string) content()->sitemap()->getBody());

        $this->assertNotFalse($xml);
        $this->assertCount(0, $xml->url);
    }

    public function testWithTheConfigOffTheSitemapRouteIsntServed(): void
    {
        $this->reindexBlogWith("route: /blog/{slug}\n");

        $this->assertFalse(config('Content')->sitemap);
        $this->assertArrayNotHasKey('sitemap.xml', service('routes')->getRoutes('GET'));

        $this->expectException(PageNotFoundException::class);
        $this->get('sitemap.xml');
    }

    public function testWithTheConfigOnTheSitemapRouteServesTheSitemap(): void
    {
        $this->reindexBlogWith("route: /blog/{slug}\n");
        config('Content')->sitemap = true;

        $result = $this->get('sitemap.xml');

        $result->assertOK();
        $this->assertStringStartsWith('application/xml', $result->response()->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('<loc>' . site_url('blog/first-post') . '</loc>', (string) $result->response()->getBody());
    }

    public function testAnAppRouteWinsOverTheSitemapRoute(): void
    {
        $this->reindexBlogWith("route: /blog/{slug}\n");
        config('Content')->sitemap = true;

        // Before discovery, as the app's Routes.php is.
        service('routes')->get('sitemap.xml', static fn (): string => 'app route');

        $this->get('sitemap.xml')->assertSee('app route');
    }

    public function testADeveloperCanServeItFromTheirOwnRoute(): void
    {
        $this->reindexBlogWith("route: /blog/{slug}\n");

        service('routes')->get('feeds/sitemap', static fn () => content()->sitemap());

        $result = $this->get('feeds/sitemap');

        $result->assertOK();
        $this->assertStringContainsString('<loc>' . site_url('blog/first-post') . '</loc>', (string) $result->response()->getBody());
    }

    /**
     * Adds a routed pages collection, with one published entry at /about.
     */
    private function addPagesCollection(): void
    {
        mkdir($this->site . 'collections/pages');
        $this->writeFile('collections/pages.yaml', "title: Pages\nroute: /{slug}\n");
        $this->writeFile('collections/pages/about.md', "---\nid: " . self::ABOUT . "\ntitle: About\n---\n");
        (new Indexer($this->site))->reindex();
    }

    /**
     * @return list<string> the <loc> of each <url>, in the order given
     */
    private function locations(): array
    {
        $xml = simplexml_load_string((string) content()->sitemap()->getBody());

        $this->assertNotFalse($xml, 'The body is not well-formed XML.');

        $locations = [];

        foreach ($xml->url as $url) {
            $locations[] = (string) $url->loc;
        }

        return $locations;
    }

    private function resetRouting(): void
    {
        Services::resetSingle('routes');
        Services::resetSingle('router');
    }
}
