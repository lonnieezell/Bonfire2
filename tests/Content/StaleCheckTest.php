<?php

namespace Tests\Content;

use Bonfire\Content\Entry;
use Bonfire\Content\StaleCheck;
use Tests\Support\Content\ContentTestCase;

/**
 * @internal
 */
final class StaleCheckTest extends ContentTestCase
{
    private const FIRST_POST = '11111111-1111-4111-8111-111111111111';
    private const FIRST_FILE = 'collections/blog/2026-01-10.first-post.md';

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockCache();
    }

    public function testAnEditedFileIsIndexed(): void
    {
        $this->check();
        $this->writeFile(self::FIRST_FILE, "---\nid: " . self::FIRST_POST . "\ntitle: Edited\n---\nEdited.\n");

        $this->check();

        $this->assertSame('Edited', content()->find(self::FIRST_POST)->title);
    }

    public function testANewFileIsIndexed(): void
    {
        $this->check();
        $this->writeFile('collections/blog/2026-05-01.new.md', "---\nid: 66666666-6666-4666-8666-666666666666\n---\nNew.\n");

        $this->check();

        $this->assertInstanceOf(Entry::class, content()->find('66666666-6666-4666-8666-666666666666'));
    }

    public function testADeletedFileIsRemoved(): void
    {
        $this->check();
        unlink($this->site . self::FIRST_FILE);

        $this->check();

        $this->assertNull(content()->find(self::FIRST_POST));
    }

    public function testAChangedCollectionConfigIsIndexed(): void
    {
        $this->check();
        $this->writeFile('collections/blog.yaml', "title: Blog\ndated: true\nroute: /blog/{slug}\n");

        $this->check();

        $this->assertSame('/blog/first-post', content()->find(self::FIRST_POST)->uri);
    }

    public function testNothingIsIndexedWhileNoFileHasChanged(): void
    {
        $this->check();
        // Not through a file: a row that disagrees with its file shows whether a reindex ran.
        db_connect()->table('content_entries')->where('id', self::FIRST_POST)->update(['title' => 'Untouched']);

        $this->check();

        $this->assertSame('Untouched', content()->find(self::FIRST_POST)->title);
    }

    public function testAnEmptiedIndexIsRebuiltThoughNoFileChanged(): void
    {
        $this->check();
        db_connect()->table('content_entries')->truncate();

        $this->check();

        $this->seeNumRecords(4, 'content_entries', []);
    }

    public function testASiteWithNoContentFolderIsLeftAlone(): void
    {
        (new StaleCheck($this->site . 'missing/'))->run();

        $this->seeNumRecords(0, 'content_entries', []);
    }

    public function testAFileThatCantBeIndexedDoesntStopTheOthers(): void
    {
        $this->writeFile(self::FIRST_FILE, 'no front matter');

        $this->check();

        $this->assertNull(content()->find(self::FIRST_POST));
        $this->assertInstanceOf(Entry::class, content()->find('22222222-2222-4222-8222-222222222222'));
        $this->assertLogged('error', 'Content: ' . self::FIRST_FILE . ': The file has no front matter block.');
    }

    private function check(): void
    {
        (new StaleCheck($this->site))->run();
    }
}
