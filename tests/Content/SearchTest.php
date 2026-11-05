<?php

namespace Tests\Content;

use Bonfire\Content\ContentException;
use Bonfire\Content\Entry;
use Bonfire\Content\Indexer;
use CodeIgniter\I18n\Time;
use Config\Services;
use Tests\Support\Content\ContentTestCase;

/**
 * @internal
 */
final class SearchTest extends ContentTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Time::setTestNow('2026-06-01 12:00:00');

        mkdir($this->site . 'collections/notes', 0777, true);
        $this->writeFile('collections/notes.yaml', "title: Notes\n");
        $this->note('aaaaaaaa-1111-4111-8111-111111111111', 'Zebra crossing', 'Plain body.');
        $this->note('aaaaaaaa-2222-4222-8222-222222222222', 'Plain note', 'A giraffe walks by the post office.');
        $this->note('aaaaaaaa-3333-4333-8333-333333333333', 'Sale', 'Everything is 100% off and a_b is a code.');
        $this->note('aaaaaaaa-4444-4444-8444-444444444444', 'Quote', "The zebra's stripes.");
        $this->note('aaaaaaaa-5555-4555-8555-555555555555', 'Drafted giraffe', 'Hidden.', "status: draft\n");
        $this->note('aaaaaaaa-6666-4666-8666-666666666666', 'Later giraffe', 'Hidden.', "status: published\npublished_at: 2099-01-01 00:00:00\n");

        (new Indexer($this->site))->reindex();
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        service('superglobals')->setGetArray([]);
        Services::resetSingle('pager');

        parent::tearDown();
    }

    private function note(string $id, string $title, string $body, string $extra = ''): void
    {
        $this->writeFile('collections/notes/' . strtolower(str_replace(' ', '-', $title)) . '.md', "---\nid: {$id}\ntitle: {$title}\n{$extra}---\n{$body}\n");
    }

    /**
     * @param list<Entry> $entries
     *
     * @return list<string>
     */
    private function titles(array $entries): array
    {
        return array_map(static fn (Entry $entry): string => $entry->title, $entries);
    }

    public function testAWordOnlyInTheTitleIsFoundAcrossCollections(): void
    {
        $this->assertSame(['Zebra crossing'], $this->titles(content()->search('crossing')->get()));
    }

    public function testAWordOnlyInTheBodyIsFound(): void
    {
        $this->assertSame(['Plain note'], $this->titles(content()->search('giraffe')->get()));
    }

    public function testEntriesOfEveryCollectionAreSearched(): void
    {
        $this->assertSame(['First post', 'Plain note', 'Second post'], $this->titles(content()->search('post')->get()), 'In the titles of the blog and the body of a note.');
    }

    public function testTheSearchCanBeNarrowedToOneCollection(): void
    {
        $this->assertSame(['Plain note'], $this->titles(content()->search('post', 'notes')->get()));
        $this->assertSame(['First post', 'Second post'], $this->titles(content()->search('post', 'blog')->get()));
        $this->assertSame([], $this->titles(content()->search('second', 'notes')->get()));
    }

    public function testNarrowingToAMissingCollectionThrows(): void
    {
        $this->expectException(ContentException::class);

        content()->search('post', 'nope');
    }

    public function testOnlyPublishedEntriesAreReturned(): void
    {
        $this->assertSame(['Plain note'], $this->titles(content()->search('giraffe')->get()), 'No draft, scheduled entry.');
        $this->assertSame([], $this->titles(content()->search('Not ready')->get()), "The blog's draft.");
        $this->assertSame([], $this->titles(content()->search('Not yet')->get()), "The blog's scheduled entry.");
    }

    public function testATrashedEntryIsNotReturned(): void
    {
        $this->assertCount(1, content()->search('crossing')->get());

        db_connect()->table('content_entries')->where('title', 'Zebra crossing')->update(['deleted_at' => '2026-05-01 00:00:00']);

        $this->assertSame([], content()->search('crossing')->get());
    }

    public function testPaginateAndPagerWorkOnTheResults(): void
    {
        $query = content()->search('post');

        $this->assertSame(['First post'], $this->titles($query->paginate(1)));
        $this->assertSame(3, $query->pager()->getTotal());

        $this->assertSame(['Plain note'], $this->titles(content()->search('post')->paginate(1, 'default', 2)));
    }

    public function testThePagerLinksReachTheSecondPage(): void
    {
        service('superglobals')->setGetArray(['q' => 'post']);
        Services::resetSingle('pager');

        $query = content()->search('post');
        $query->paginate(1);

        $links = html_entity_decode($query->pager()->links());

        $this->assertMatchesRegularExpression('/[?&]page=2/', $links);
        $this->assertStringContainsString('q=post', $links);
    }

    public function testPercentAndUnderscoreMatchLiterally(): void
    {
        $this->assertSame(['Sale'], $this->titles(content()->search('100%')->get()));
        $this->assertSame(['Sale'], $this->titles(content()->search('a_b')->get()));
        $this->assertSame([], $this->titles(content()->search('1%0')->get()), 'A % is not "any characters".');
        $this->assertSame([], $this->titles(content()->search('a_c')->get()), 'A _ is not "any one character".');
    }

    public function testAQuoteInTheTermWorks(): void
    {
        $this->assertSame(['Quote'], $this->titles(content()->search("zebra's")->get()));
        $this->assertSame([], $this->titles(content()->search("' OR 1=1 --")->get()));
    }

    public function testSpacesAroundTheTermAreIgnored(): void
    {
        $this->assertSame(['Zebra crossing'], $this->titles(content()->search('  crossing ')->get()));
    }

    public function testABlankTermFindsNothing(): void
    {
        $this->assertSame([], content()->search('')->get());
        $this->assertSame([], content()->search('  ')->get());
    }

    public function testTheResultsAreInTitleOrderUnlessOrderByChangesIt(): void
    {
        $this->assertSame(['First post', 'Plain note', 'Second post'], $this->titles(content()->search('post')->get()));
        $this->assertSame(['Second post', 'Plain note', 'First post'], $this->titles(content()->search('post')->orderBy('title', 'desc')->get()));
    }

    public function testWhereTermNeedsACollection(): void
    {
        $this->expectException(ContentException::class);

        content()->search('post')->whereTerm('tags', 'php');
    }
}
