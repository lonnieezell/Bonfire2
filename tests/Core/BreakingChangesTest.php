<?php

namespace Tests\Core;

use Bonfire\Core\BreakingChanges;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class BreakingChangesTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        helper('filesystem');

        $this->directory = sys_get_temp_dir() . '/bonfire-' . bin2hex(random_bytes(8)) . '/';
        mkdir($this->directory, 0777, true);
    }

    protected function tearDown(): void
    {
        delete_files($this->directory, true, true);

        parent::tearDown();
    }

    public function testLatestReadsTheNewestBreakingChange()
    {
        $breakingChanges = $this->withChangelog(<<<'MARKDOWN'
            # Change Log

            **IMPORTANT!** *Breaking changes* are marked with words `breaking change` in parentheses right after the date.

            ## 20 January 2025

            A feature.

            ## 18 March 2024 (breaking change)

            An old break.

            ## 11 October 2026 (breaking change)

            The newest break.

            ## 9 October 2026 (breaking change)

            A break in between.
            MARKDOWN);

        $this->assertSame('2026-10-11', $breakingChanges->latest()['date']);
    }

    public function testLatestIgnoresHeadingsWithoutTheMarker()
    {
        $breakingChanges = $this->withChangelog(<<<'MARKDOWN'
            # Change Log

            **IMPORTANT!** *Breaking changes* are marked with words `breaking change` in parentheses right after the date.

            ## 20 January 2025

            A feature that mentions a (breaking change) in its body.
            MARKDOWN);

        $this->assertTrue($breakingChanges->hasChangelog());
        $this->assertNull($breakingChanges->latest());
    }

    public function testLatestReturnsNullWhenTheChangelogIsMissing()
    {
        $breakingChanges = new BreakingChanges($this->directory . 'missing.md', $this->directory . 'state.json');

        $this->assertFalse($breakingChanges->hasChangelog());
        $this->assertNull($breakingChanges->latest());
    }

    public function testLatestNoticesTextAddedToAnExistingBreakingHeading()
    {
        $breakingChanges = $this->withChangelog(<<<'MARKDOWN'
            ## 8 October 2026 (breaking change)

            A break.
            MARKDOWN);

        $first = $breakingChanges->latest();
        $this->assertTrue($breakingChanges->acknowledge($first));

        $this->withChangelog(<<<'MARKDOWN'
            ## 8 October 2026 (breaking change)

            A break.

            Another note added to the same heading a day later.
            MARKDOWN);

        $second = $breakingChanges->latest();

        $this->assertSame($first['date'], $second['date']);
        $this->assertNotSame($first['hash'], $second['hash']);
        $this->assertTrue($breakingChanges->isNew($second, $breakingChanges->acknowledged()));
    }

    public function testEverythingIsNewWithoutAMarker()
    {
        $latest = ['date' => '2026-10-28', 'hash' => 'abc'];

        $this->assertTrue((new BreakingChanges())->isNew($latest, null));
    }

    public function testAnOlderBreakingChangeIsNotNew()
    {
        $breakingChanges = new BreakingChanges();

        $this->assertFalse($breakingChanges->isNew(
            ['date' => '2026-10-08', 'hash' => 'abc'],
            ['date' => '2026-10-28', 'hash' => 'abc'],
        ));
    }

    public function testANewerBreakingChangeIsNew()
    {
        $breakingChanges = new BreakingChanges();

        $this->assertTrue($breakingChanges->isNew(
            ['date' => '2026-10-28', 'hash' => 'abc'],
            ['date' => '2026-10-08', 'hash' => 'abc'],
        ));
    }

    public function testAMarkerOfAnOlderVersionComparesDatesOnly()
    {
        $breakingChanges = new BreakingChanges();

        $this->assertFalse($breakingChanges->isNew(
            ['date' => '2026-10-28', 'hash' => 'abc'],
            ['date' => '2026-10-28', 'hash' => ''],
        ));
    }

    public function testAcknowledgeWritesTheMarkerAndCreatesItsDirectory()
    {
        $path            = $this->directory . 'nested/bonfire/breaking-changes.json';
        $breakingChanges = new BreakingChanges(null, $path);

        $this->assertTrue($breakingChanges->acknowledge(['date' => '2026-10-28', 'hash' => 'abc']));
        $this->assertFileExists($path);
        $this->assertSame(
            ['date' => '2026-10-28', 'hash' => 'abc'],
            (new BreakingChanges(null, $path))->acknowledged(),
        );
    }

    public function testAcknowledgedReturnsNullForAMissingOrBrokenMarker()
    {
        $path = $this->directory . 'breaking-changes.json';

        $this->assertNull((new BreakingChanges(null, $path))->acknowledged());

        file_put_contents($path, 'not json');

        $this->assertNull((new BreakingChanges(null, $path))->acknowledged());
    }

    public function testAcknowledgeFailsWhenItsDirectoryIsAFile()
    {
        $file = $this->directory . 'state';
        file_put_contents($file, '');

        $breakingChanges = new BreakingChanges(null, $file . '/breaking-changes.json');

        $this->assertFalse($breakingChanges->acknowledge(['date' => '2026-10-28', 'hash' => 'abc']));
    }

    public function testMigrateLegacyCarriesTheOldMarkerOverAndRemovesIt()
    {
        $legacy = $this->directory . 'last-update.txt';
        file_put_contents($legacy, '2026-09-01');

        $breakingChanges = new BreakingChanges(null, $this->directory . 'breaking-changes.json');

        $this->assertTrue($breakingChanges->migrateLegacy($legacy));
        $this->assertFileDoesNotExist($legacy);
        $this->assertSame(['date' => '2026-09-01', 'hash' => ''], $breakingChanges->acknowledged());
    }

    public function testMigrateLegacyLeavesAnExistingMarkerAlone()
    {
        $legacy = $this->directory . 'last-update.txt';
        file_put_contents($legacy, '2026-09-01');

        $breakingChanges = new BreakingChanges(null, $this->directory . 'breaking-changes.json');
        $breakingChanges->acknowledge(['date' => '2026-10-28', 'hash' => 'abc']);

        $this->assertFalse($breakingChanges->migrateLegacy($legacy));
        $this->assertSame('2026-10-28', $breakingChanges->acknowledged()['date']);
    }

    public function testTheDefaultMarkerIsKeptOutsideThePackageFolder()
    {
        $this->assertStringStartsWith(WRITEPATH, BreakingChanges::defaultStatePath());
    }

    public function testTheDefaultChangelogIsTheOneThatShipsWithThePackage()
    {
        $this->assertFileExists(BreakingChanges::defaultChangelogPath());
        $this->assertNotFalse((new BreakingChanges())->latest());
    }

    /**
     * Writes a changelog the tracker can read and returns a tracker for it.
     */
    private function withChangelog(string $contents): BreakingChanges
    {
        $path = $this->directory . 'changelog.md';

        file_put_contents($path, $contents . "\n");

        return new BreakingChanges($path, $this->directory . 'breaking-changes.json');
    }
}
