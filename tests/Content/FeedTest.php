<?php

namespace Tests\Content;

use Bonfire\Content\ContentException;
use Bonfire\Content\Indexer;
use Bonfire\Content\Models\ContentEntryModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\I18n\Time;
use Config\Services;
use DateTimeImmutable;
use DOMDocument;
use DOMXPath;
use Tests\Support\Content\ContentTestCase;

/**
 * The RSS feed of a collection, from content()->feed() and the optional /feed/{collection} route.
 *
 * @internal
 */
final class FeedTest extends ContentTestCase
{
    private const FIRST = '11111111-1111-4111-8111-111111111111';

    protected function setUp(): void
    {
        parent::setUp();

        Time::setTestNow('2026-06-01 12:00:00');

        $this->reindexBlogWith("route: /blog/{slug}\ntemplate: blog/show\n");
        $this->resetRouting();
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        $this->resetRouting();

        parent::tearDown();
    }

    // content()->feed()

    public function testTheFeedIsAnRssResponseOfWellFormedXml(): void
    {
        $response = content()->feed('blog');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringStartsWith('application/rss+xml', $response->getHeaderLine('Content-Type'));

        $xml = $this->parse((string) $response->getBody());

        $this->assertSame('rss', $xml->documentElement->nodeName);
        $this->assertSame('2.0', $xml->documentElement->getAttribute('version'));
        $this->assertSame('Blog', $this->xpath($xml)->evaluate('string(/rss/channel/title)'));
        $this->assertSame(site_url(), $this->xpath($xml)->evaluate('string(/rss/channel/link)'));
    }

    public function testEachItemHasATitleALinkAGuidAndTheDate(): void
    {
        $xml  = $this->parse((string) content()->feed('blog')->getBody());
        $item = $this->xpath($xml)->query('/rss/channel/item')->item(0);

        $this->assertNotNull($item);
        $this->assertSame('Second post', $this->xpath($xml)->evaluate('string(title)', $item));
        $this->assertSame(site_url('blog/second-post'), $this->xpath($xml)->evaluate('string(link)', $item));
        $this->assertSame(site_url('blog/second-post'), $this->xpath($xml)->evaluate('string(guid)', $item));
        $this->assertSame(
            (new DateTimeImmutable('2026-02-15 00:00:00'))->format(DATE_RSS),
            $this->xpath($xml)->evaluate('string(pubDate)', $item),
        );
    }

    public function testEntryValuesAreEscaped(): void
    {
        $this->writeFile('collections/blog/2026-04-01.escaped.md', "---\nid: 99999999-9999-4999-8999-999999999999\ntitle: 'Tom & <Jerry> \"and\" ]]> friends'\n---\nBody.\n");
        (new Indexer($this->site))->reindex();

        $response = content()->feed('blog');
        $raw      = (string) $response->getBody();
        $xml      = $this->parse($raw);

        $this->assertStringNotContainsString('<Jerry>', $raw);
        $this->assertContains('Tom & <Jerry> "and" ]]> friends', $this->titles($xml));
    }

    public function testCharactersXmlCantHoldDontBreakTheFeed(): void
    {
        $this->writeFile('collections/blog/2026-04-01.control.md', "---\nid: 99999999-9999-4999-8999-999999999999\ntitle: \"Bell \\a here\"\n---\nBody.\n");
        (new Indexer($this->site))->reindex();

        $this->assertContains('Bell  here', $this->titles($this->parse((string) content()->feed('blog')->getBody())));
    }

    public function testOnlyPublishedEntriesAreIncluded(): void
    {
        (new ContentEntryModel())->delete(self::FIRST);

        $titles = $this->titles($this->parse((string) content()->feed('blog')->getBody()));

        // The draft, the scheduled post and the trashed one are all missing.
        $this->assertSame(['Second post'], $titles);
    }

    public function testEntriesAreInTheCollectionsOrder(): void
    {
        $titles = $this->titles($this->parse((string) content()->feed('blog')->getBody()));

        $this->assertSame(['Second post', 'First post'], $titles);
    }

    public function testAnEntryWithNoUriIsLeftOut(): void
    {
        (new ContentEntryModel())->update(self::FIRST, ['uri' => null]);

        $xml = $this->parse((string) content()->feed('blog')->getBody());

        $this->assertSame(['Second post'], $this->titles($xml));
    }

    public function testACollectionWithNoRouteGivesAnEmptyFeed(): void
    {
        $this->reindexBlogWith('');

        $xml = $this->parse((string) content()->feed('blog')->getBody());

        $this->assertSame([], $this->titles($xml));
        $this->assertSame('Blog', $this->xpath($xml)->evaluate('string(/rss/channel/title)'));
    }

    public function testAnUnknownCollectionThrows(): void
    {
        $this->expectException(ContentException::class);

        content()->feed('ghost');
    }

    // The /feed/{collection} route

    public function testWithTheRouteOffTheFeedIsNotServed(): void
    {
        $this->assertFalse(config('Content')->feedRoute);

        $this->assertNotFound('feed/blog');
        $this->assertArrayNotHasKey('feed/(:segment)', service('routes')->loadRoutes()->getRoutes('GET'));
    }

    public function testWithTheRouteOnTheFeedIsServed(): void
    {
        config('Content')->feedRoute = true;

        $result = $this->get('feed/blog');

        $result->assertOK();
        $this->assertStringStartsWith('application/rss+xml', $result->response()->getHeaderLine('Content-Type'));
        $this->assertSame(['Second post', 'First post'], $this->titles($this->parse((string) $result->response()->getBody())));
    }

    public function testAnUnknownCollectionIsA404(): void
    {
        config('Content')->feedRoute = true;

        $this->assertNotFound('feed/ghost');
        $this->assertNotFound('feed/..%2Fsecrets');
    }

    public function testTheRouteWorksWithTheFallbackRouteOn(): void
    {
        config('Content')->feedRoute = true;
        config('Content')->autoRoute = true;

        // The fallback route would answer this with a 404, as no entry has this URI.
        $result = $this->get('feed/blog');

        $result->assertOK();
        $this->assertStringStartsWith('application/rss+xml', $result->response()->getHeaderLine('Content-Type'));
    }

    public function testAnAppRouteAtTheSameUrlWins(): void
    {
        config('Content')->feedRoute = true;

        service('routes')->get('feed/blog', static fn (): string => 'app route');

        $this->get('feed/blog')->assertSee('app route');
    }

    public function testAnAppRouteWithTheSamePatternWins(): void
    {
        config('Content')->feedRoute = true;

        service('routes')->get('feed/(:segment)', static fn (string $handle): string => "app feed of {$handle}");

        $this->get('feed/blog')->assertSee('app feed of blog');
    }

    public function testTheAppCanServeTheFeedFromItsOwnRoute(): void
    {
        service('routes')->get('rss.xml', static fn () => content()->feed('blog'));

        $result = $this->get('rss.xml');

        $result->assertOK();
        $this->assertSame(['Second post', 'First post'], $this->titles($this->parse((string) $result->response()->getBody())));
    }

    private function parse(string $xml): DOMDocument
    {
        $dom = new DOMDocument();

        $this->assertTrue($dom->loadXML($xml, LIBXML_NOERROR | LIBXML_NOWARNING), 'The feed is not well-formed XML.');

        return $dom;
    }

    private function xpath(DOMDocument $xml): DOMXPath
    {
        return new DOMXPath($xml);
    }

    /**
     * @return list<string>
     */
    private function titles(DOMDocument $xml): array
    {
        $titles = [];

        foreach ($this->xpath($xml)->query('/rss/channel/item/title') as $title) {
            $titles[] = $title->textContent;
        }

        return $titles;
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
