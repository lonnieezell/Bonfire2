<?php

namespace Tests\Content;

use Bonfire\Content\Indexer;
use Bonfire\Content\ReindexResult;
use CodeIgniter\Database\Query;
use CodeIgniter\Events\Events;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Content\ContentTestCase;

/**
 * @internal
 */
final class IndexerTest extends ContentTestCase
{
    private const FIRST_POST  = '11111111-1111-4111-8111-111111111111';
    private const SECOND_POST = '22222222-2222-4222-8222-222222222222';
    private const NEW_ENTRY   = '66666666-6666-4666-8666-666666666666';
    private const FIRST_FILE  = 'collections/blog/2026-01-10.first-post.md';

    public function testCreatesOneRowPerEntryWithItsFrontMatterInData(): void
    {
        $result = $this->reindex();

        $this->assertSame(4, $result->added);
        $this->assertSame([], $result->errors);
        $this->seeNumRecords(4, 'content_entries', []);

        $row = $this->row(self::FIRST_POST);
        $this->assertSame('blog', $row['collection']);
        $this->assertSame('post', $row['blueprint']);
        $this->assertSame('first-post', $row['slug']);
        $this->assertSame('First post', $row['title']);
        $this->assertSame('published', $row['status']);
        $this->assertSame('2026-01-10 00:00:00', $row['published_at']);
        $this->assertSame(self::FIRST_FILE, $row['path']);
        $this->assertSame("The first *post*.\n", $row['body']);
        $this->assertSame([
            'id'      => self::FIRST_POST,
            'title'   => 'First post',
            'tagline' => 'Where it starts',
        ], json_decode($row['data'], true));
    }

    public function testASecondReindexWithNoChangesWritesNothing(): void
    {
        $this->reindex();

        $writes = $this->writesDuring(fn () => $this->reindex());

        $this->assertSame([], $writes);
    }

    public function testASecondReindexReportsEveryEntryAsUnchanged(): void
    {
        $this->reindex();

        $result = $this->reindex();

        $this->assertSame([0, 0, 0, 4], [$result->added, $result->updated, $result->removed, $result->unchanged]);
    }

    public function testEditingOneFileUpdatesOnlyThatRow(): void
    {
        $this->reindex();
        $this->writeFile(self::FIRST_FILE, "---\nid: " . self::FIRST_POST . "\ntitle: Edited\n---\nNew body.\n");

        $writes = $this->writesDuring(fn () => $this->reindex());

        $this->assertCount(1, $writes);
        $this->assertStringStartsWith('UPDATE', $writes[0]);
        $this->assertSame('Edited', $this->row(self::FIRST_POST)['title']);
        $this->assertSame("New body.\n", $this->row(self::FIRST_POST)['body']);
        $this->assertSame('Second post', $this->row(self::SECOND_POST)['title']);
    }

    public function testDeletingAFileRemovesItsRow(): void
    {
        $this->reindex();
        unlink($this->site . self::FIRST_FILE);

        $result = $this->reindex();

        $this->assertSame(1, $result->removed);
        $this->dontSeeInDatabase('content_entries', ['id' => self::FIRST_POST]);
        $this->seeNumRecords(3, 'content_entries', []);
    }

    public function testRenamingAFileKeepsItsRow(): void
    {
        $this->reindex();
        rename($this->site . self::FIRST_FILE, $this->site . 'collections/blog/2026-01-11.renamed.md');

        $result = $this->reindex();

        $this->assertSame([0, 1, 0], [$result->added, $result->updated, $result->removed]);
        $row = $this->row(self::FIRST_POST);
        $this->assertSame('renamed', $row['slug']);
        $this->assertSame('2026-01-11 00:00:00', $row['published_at']);
        $this->assertSame('collections/blog/2026-01-11.renamed.md', $row['path']);
    }

    public function testChangingAFilesIdReplacesItsRow(): void
    {
        $newId = '55555555-5555-4555-8555-555555555555';
        $this->reindex();
        $this->writeFile(self::FIRST_FILE, "---\nid: {$newId}\n---\n");

        $result = $this->reindex();

        $this->assertSame([1, 1], [$result->added, $result->removed]);
        $this->dontSeeInDatabase('content_entries', ['id' => self::FIRST_POST]);
        $this->seeInDatabase('content_entries', ['id' => $newId, 'path' => self::FIRST_FILE]);
    }

    public function testChangingACollectionConfigReachesItsUnchangedEntries(): void
    {
        $this->reindex();
        $this->writeFile('collections/blog.yaml', "title: Blog\ndated: true\nblueprints: [article]\n");

        $this->assertSame(4, $this->reindex()->updated);
        $this->assertSame('article', $this->row(self::FIRST_POST)['blueprint']);
    }

    public function testFreshMatchesACleanIncrementalRun(): void
    {
        $this->reindex();
        $clean = $this->rows();

        // Damage that an incremental run has no need to see, plus a row with no file.
        db_connect()->table('content_entries')->where('id', self::SECOND_POST)->delete();
        db_connect()->table('content_entries')->insert(['id' => 'gone'] + $this->row(self::FIRST_POST));

        $result = $this->reindex(true);

        $this->assertSame(4, $result->added);
        $this->assertSame($clean, $this->rows());
    }

    #[DataProvider('provideReportsAnEntryItCantIndexAndIndexesTheRest')]
    public function testReportsAnEntryItCantIndexAndIndexesTheRest(string $contents, string $reason): void
    {
        $this->writeFile('collections/blog/broken.md', $contents);

        $result = $this->reindex();

        $this->assertSame(['collections/blog/broken.md'], array_keys($result->errors));
        $this->assertStringContainsString($reason, $result->errors['collections/blog/broken.md']);
        $this->assertSame(4, $result->added);
        $this->seeNumRecords(4, 'content_entries', []);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideReportsAnEntryItCantIndexAndIndexesTheRest(): iterable
    {
        yield 'no front matter' => ["Just Markdown.\n", 'no front matter'];

        yield 'unclosed front matter' => ["---\nid: " . self::NEW_ENTRY . "\nNo closing line\n", 'no front matter'];

        yield 'malformed YAML' => ["---\nid: [unclosed\n---\nBody\n", 'Malformed front matter'];

        yield 'not a mapping' => ["---\njust a string\n---\nBody\n", 'mapping'];

        yield 'no id' => ["---\ntitle: No id\n---\nBody\n", '`id`'];

        yield 'id is not a UUID' => ["---\nid: 12\n---\nBody\n", '`id`'];

        yield 'unknown status' => ["---\nid: " . self::NEW_ENTRY . "\nstatus: pending\n---\n", '`status`'];

        yield 'bad published_at' => ["---\nid: " . self::NEW_ENTRY . "\npublished_at: soon\n---\n", '`published_at`'];

        yield 'empty published_at' => ["---\nid: " . self::NEW_ENTRY . "\npublished_at: ''\n---\n", '`published_at`'];

        yield 'relative published_at' => ["---\nid: " . self::NEW_ENTRY . "\npublished_at: tomorrow\n---\n", '`published_at`'];

        yield 'overflowing published_at' => ["---\nid: " . self::NEW_ENTRY . "\npublished_at: '2026-02-31'\n---\n", '`published_at`'];

        yield 'blueprint too long' => ["---\nid: " . self::NEW_ENTRY . "\npublished_at: 2026-01-01\nblueprint: " . str_repeat('b', 101) . "\n---\n", '`blueprint`'];

        yield 'not UTF-8' => ["---\nid: " . self::NEW_ENTRY . "\ntitle: \xFF\n---\n", 'UTF-8'];
    }

    public function testAFileThatBreaksLosesItsRowLikeARebuildWould(): void
    {
        $this->reindex();
        $this->writeFile(self::FIRST_FILE, "---\ntitle: Lost its id\n---\n");

        $result = $this->reindex();

        $this->assertArrayHasKey(self::FIRST_FILE, $result->errors);
        $this->dontSeeInDatabase('content_entries', ['id' => self::FIRST_POST]);
    }

    public function testReportsAnEntryRepeatingAnotherEntrysId(): void
    {
        $this->writeFile('collections/blog/2099-12-31.copy.md', "---\nid: " . self::FIRST_POST . "\n---\n");

        $result = $this->reindex();

        $this->assertStringContainsString(self::FIRST_FILE, $result->errors['collections/blog/2099-12-31.copy.md']);
        $this->seeNumRecords(4, 'content_entries', []);
        $this->assertSame(self::FIRST_FILE, $this->row(self::FIRST_POST)['path']);
    }

    public function testReportsAnInvalidCollectionConfig(): void
    {
        $this->writeFile('collections/blog.yaml', "sort: [unclosed\n");

        $result = $this->reindex();

        $this->assertArrayHasKey('collections/blog.yaml', $result->errors);
        $this->seeNumRecords(0, 'content_entries', []);
    }

    public function testRejectsACollectionSortedByAFieldThatIsNotIndexed(): void
    {
        $this->writeFile('collections/blog.yaml', "sort: sort desc\n");

        $this->assertStringContainsString('sort', $this->reindex()->errors['collections/blog.yaml']);
    }

    public function testAnUndatedFilenameInADatedCollectionIsReported(): void
    {
        $this->writeFile('collections/blog/2099-1-1.launch.md', "---\nid: " . self::NEW_ENTRY . "\n---\n");

        $result = $this->reindex();

        $this->assertArrayHasKey('collections/blog/2099-1-1.launch.md', $result->errors);
        $this->dontSeeInDatabase('content_entries', ['id' => self::NEW_ENTRY]);
    }

    public function testAnUndatedFilenameIsFineWithAnExplicitDate(): void
    {
        $this->writeFile('collections/blog/launch.md', "---\nid: " . self::NEW_ENTRY . "\npublished_at: 2026-01-01\n---\n");

        $this->assertSame([], $this->reindex()->errors);
        $this->seeInDatabase('content_entries', ['slug' => 'launch', 'published_at' => '2026-01-01 00:00:00']);
    }

    public function testConvertsADateWithAnOffsetToTheAppTimezone(): void
    {
        $timezone = date_default_timezone_get();
        date_default_timezone_set('UTC');

        try {
            $this->writeFile(self::FIRST_FILE, "---\nid: " . self::FIRST_POST . "\npublished_at: 2026-05-06T07:08:09+02:00\nreviewed: 2026-05-07T01:00:00+02:00\n---\n");
            $this->writeFile('collections/blog/2026-01-12.quoted.md', "---\nid: " . self::NEW_ENTRY . "\npublished_at: '2026-05-06T07:08:09+02:00'\n---\n");

            $this->assertSame([], $this->reindex()->errors);
        } finally {
            date_default_timezone_set($timezone);
        }

        $row = $this->row(self::FIRST_POST);
        $this->assertSame('2026-05-06 05:08:09', $row['published_at']);
        $this->assertSame('2026-05-06 23:00:00', json_decode($row['data'], true)['reviewed']);
        $this->assertSame('2026-05-06 05:08:09', $this->row(self::NEW_ENTRY)['published_at']);
    }

    public function testReadsADateWithNoOffsetInTheAppTimezone(): void
    {
        $timezone = date_default_timezone_get();
        date_default_timezone_set('America/Chicago');

        try {
            $this->writeFile(self::FIRST_FILE, "---\nid: " . self::FIRST_POST . "\npublished_at: 2026-05-06 07:08:09\n---\n");
            $this->writeFile('collections/blog/2026-01-12.quoted.md', "---\nid: " . self::NEW_ENTRY . "\npublished_at: '2026-05-06 07:08:09'\n---\n");

            $this->assertSame([], $this->reindex()->errors);
        } finally {
            date_default_timezone_set($timezone);
        }

        $this->assertSame('2026-05-06 07:08:09', $this->row(self::FIRST_POST)['published_at']);
        $this->assertSame('2026-05-06 07:08:09', $this->row(self::NEW_ENTRY)['published_at']);
    }

    public function testAFilenameWithAnImpossibleDateIsReported(): void
    {
        $this->writeFile('collections/blog/2026-02-31.bad.md', "---\nid: " . self::NEW_ENTRY . "\n---\n");

        $this->assertArrayHasKey('collections/blog/2026-02-31.bad.md', $this->reindex()->errors);
    }

    public function testTouchingAFileWritesNothing(): void
    {
        $this->reindex();
        touch($this->site . self::FIRST_FILE, time() + 100);

        $this->assertSame([], $this->writesDuring(fn () => $this->reindex()));
    }

    public function testAFailedWriteIsReportedAndRollsBack(): void
    {
        $this->reindex();
        unlink($this->site . self::FIRST_FILE);
        $this->writeFile('collections/blog/2026-01-12.new.md', "---\nid: " . self::NEW_ENTRY . "\n---\n");
        db_connect()->query('CREATE TRIGGER refuse_writes BEFORE INSERT ON ' . db_connect()->prefixTable('content_entries') . " BEGIN SELECT RAISE(ABORT, 'refused'); END");

        try {
            $result = $this->reindex();
        } finally {
            db_connect()->query('DROP TRIGGER refuse_writes');
        }

        $this->assertSame([0, 0, 0], [$result->added, $result->updated, $result->removed]);
        $this->assertNotNull($result->failure);
        $this->seeInDatabase('content_entries', ['id' => self::FIRST_POST]);
    }

    public function testAnEmptyContentRootIsReportedAndLeavesTheIndexAlone(): void
    {
        $this->reindex();
        mkdir($this->site . 'empty-mount');

        $result = (new Indexer($this->site . 'empty-mount/'))->reindex();

        $this->assertSame(0, $result->removed);
        $this->assertNotNull($result->failure);
        $this->seeNumRecords(4, 'content_entries', []);
    }

    public function testReadsAContentRootWithGlobCharactersInItsPath(): void
    {
        $this->reindex();
        mkdir($this->site . 'site[1]');
        rename($this->site . 'collections', $this->site . 'site[1]/collections');

        $result = (new Indexer($this->site . 'site[1]/'))->reindex();

        $this->assertSame([0, 0, 4], [$result->added, $result->removed, $result->unchanged]);
    }

    public function testAMissingContentRootIsReportedAndLeavesTheIndexAlone(): void
    {
        $this->reindex();

        $result = (new Indexer($this->site . 'not-mounted/'))->reindex();

        $this->assertNotNull($result->failure);
        $this->seeNumRecords(4, 'content_entries', []);
    }

    public function testTakesTheDateFromFrontMatterOverTheFilename(): void
    {
        $this->writeFile(self::FIRST_FILE, "---\nid: " . self::FIRST_POST . "\npublished_at: 2026-05-06 07:08:09\nreviewed: 2026-05-07\n---\n");

        $this->reindex();

        $row = $this->row(self::FIRST_POST);
        $this->assertSame('2026-05-06 07:08:09', $row['published_at']);
        $this->assertSame('2026-05-07 00:00:00', json_decode($row['data'], true)['reviewed']);
    }

    private function reindex(bool $fresh = false): ReindexResult
    {
        return (new Indexer($this->site))->reindex($fresh);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(string $id): array
    {
        return db_connect()->table('content_entries')->where('id', $id)->get()->getRowArray();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rows(): array
    {
        return db_connect()->table('content_entries')->orderBy('id')->get()->getResultArray();
    }

    /**
     * The INSERT, UPDATE and DELETE statements $action runs.
     *
     * @return list<string>
     */
    private function writesDuring(callable $action): array
    {
        $writes   = [];
        $listener = static function (Query $query) use (&$writes): void {
            if (preg_match('/^\s*(INSERT|UPDATE|DELETE)/i', $query->getQuery()) === 1) {
                $writes[] = $query->getQuery();
            }
        };

        Events::on('DBQuery', $listener);

        try {
            $action();
        } finally {
            Events::removeListener('DBQuery', $listener);
        }

        return $writes;
    }
}
