<?php

namespace Tests\Content;

use Bonfire\Content\ContentException;
use Bonfire\Content\Entry;
use Bonfire\Content\Indexer;
use CodeIgniter\I18n\Time;
use Tests\Support\Content\ContentTestCase;

/**
 * @internal
 */
final class QueryTest extends ContentTestCase
{
    private const FIRST_POST = '11111111-1111-4111-8111-111111111111';
    private const DRAFT_POST = '33333333-3333-4333-8333-333333333333';

    protected function setUp(): void
    {
        parent::setUp();

        Time::setTestNow('2026-06-01 12:00:00');
        (new Indexer($this->site))->reindex();
    }

    protected function tearDown(): void
    {
        Time::setTestNow();

        parent::tearDown();
    }

    public function testPublishedExcludesDraftsAndFutureEntries(): void
    {
        $this->assertSame(['second-post', 'first-post'], $this->slugs(content()->collection('blog')->published()->get()));
    }

    public function testWithoutPublishedAllEntriesAreReturned(): void
    {
        $this->assertCount(4, content()->collection('blog')->get());
    }

    public function testAQueryKeepsItsConditionsWhenReused(): void
    {
        $query = content()->collection('blog')->published();

        $first = $this->slugs($query->get());

        $this->assertSame($first, $this->slugs($query->get()));
        $this->assertSame(['second-post'], $this->slugs($query->paginate(1)));
        $this->assertNull($query->find(self::DRAFT_POST));
    }

    public function testPublishedIncludesAnEntryWithNoDate(): void
    {
        $this->writeFile('collections/pages.yaml', "title: Pages\n");
        mkdir($this->site . 'collections/pages');
        $this->writeFile('collections/pages/about.md', "---\nid: 77777777-7777-4777-8777-777777777777\n---\nAbout.\n");
        (new Indexer($this->site))->reindex();

        $this->assertSame(['about'], $this->slugs(content()->collection('pages')->published()->get()));
    }

    public function testACollectionOnlySeesItsOwnEntries(): void
    {
        $this->writeFile('collections/pages.yaml', "title: Pages\n");

        $this->assertSame([], content()->collection('pages')->get());
    }

    public function testAnUnknownCollectionIsAnError(): void
    {
        $this->expectException(ContentException::class);

        content()->collection('nope');
    }

    public function testAHandleCantLeaveTheCollectionsFolder(): void
    {
        $this->writeFile('outside.yaml', "title: Outside\n");

        $this->expectException(ContentException::class);

        content()->collection('../outside');
    }

    public function testUsesTheCollectionsSortByDefault(): void
    {
        $this->assertSame(
            ['future-post', 'draft-post', 'second-post', 'first-post'],
            $this->slugs(content()->collection('blog')->get()),
        );
    }

    public function testOrderByReplacesTheCollectionsSort(): void
    {
        $this->assertSame(
            ['first-post', 'second-post'],
            $this->slugs(content()->collection('blog')->published()->orderBy('title')->get()),
        );
        $this->assertSame(
            ['first-post', 'second-post'],
            $this->slugs(content()->collection('blog')->published()->orderBy('published_at', 'asc')->get()),
        );
    }

    public function testOrderByRefusesAFieldThatIsNotIndexed(): void
    {
        $this->expectException(ContentException::class);

        content()->collection('blog')->orderBy('tagline; DROP TABLE users');
    }

    public function testOrderByRefusesAnUnknownDirection(): void
    {
        $this->expectException(ContentException::class);

        content()->collection('blog')->orderBy('title', 'sideways');
    }

    public function testPaginateReturnsAPageOfEntriesAndAPager(): void
    {
        $query = content()->collection('blog')->published();

        $this->assertSame(['second-post'], $this->slugs($query->paginate(1)));
        $this->assertSame(2, $query->pager()->getTotal());
        $this->assertSame(['first-post'], $this->slugs(content()->collection('blog')->published()->paginate(1, 'default', 2)));
    }

    public function testFindReturnsAnEntryByItsUuid(): void
    {
        $this->assertSame('first-post', content()->find(self::FIRST_POST)->slug);
        $this->assertNull(content()->find('00000000-0000-4000-8000-000000000000'));
    }

    public function testFindOnAQueryKeepsItsConditions(): void
    {
        $this->assertSame('draft-post', content()->collection('blog')->find(self::DRAFT_POST)->slug);
        $this->assertNull(content()->collection('blog')->published()->find(self::DRAFT_POST));

        $this->writeFile('collections/pages.yaml', "title: Pages\n");
        $this->assertNull(content()->collection('pages')->find(self::FIRST_POST));
    }

    public function testAnEntryExposesItsTitleFieldsAndRawBody(): void
    {
        $entry = content()->find(self::FIRST_POST);

        $this->assertSame('First post', $entry->title);
        $this->assertInstanceOf(Entry::class, $entry);
        $this->assertSame('Where it starts', $entry->field('tagline'));
        $this->assertNull($entry->field('missing'));
        $this->assertSame('fallback', $entry->field('missing', 'fallback'));
        $this->assertSame("The first *post*.\n", $entry->body);
        $this->assertSame('2026-01-10 00:00:00', $entry->publishedAt);
    }

    public function testContentRendersTheBodyAsGithubFlavoredMarkdown(): void
    {
        $this->writeFile(
            'collections/blog/2026-01-10.first-post.md',
            "---\nid: " . self::FIRST_POST . "\n---\n| a |\n|---|\n| b |\n",
        );
        (new Indexer($this->site))->reindex();

        $entry = content()->find(self::FIRST_POST);
        $this->assertInstanceOf(Entry::class, $entry);

        $this->assertStringContainsString('<table>', $entry->content());
        $this->assertSame("| a |\n|---|\n| b |\n", $entry->body);
    }

    public function testContentDropsUnsafeLinks(): void
    {
        $this->writeFile(
            'collections/blog/2026-01-10.first-post.md',
            "---\nid: " . self::FIRST_POST . "\n---\n[click](javascript:alert(1))\n",
        );
        (new Indexer($this->site))->reindex();

        $this->assertStringNotContainsString('javascript:', content()->find(self::FIRST_POST)->content());
    }

    /**
     * @param list<Entry> $entries
     *
     * @return list<string>
     */
    private function slugs(array $entries): array
    {
        return array_map(static fn (Entry $entry): string => $entry->slug, $entries);
    }
}
