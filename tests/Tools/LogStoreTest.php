<?php

namespace Tests\Tools;

use Bonfire\Tools\Libraries\LogStore;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class LogStoreTest extends TestCase
{
    private string $dir;
    private LogStore $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir() . '/bonfire-logs-' . uniqid('', true) . '/';
        mkdir($this->dir);

        $this->store = new LogStore($this->dir);
    }

    protected function tearDown(): void
    {
        array_map(unlink(...), glob($this->dir . '*'));
        rmdir($this->dir);

        parent::tearDown();
    }

    private function writeLog(string $name, string $content = ''): void
    {
        file_put_contents($this->dir . $name, $content);
    }

    public function testNamesListsOnlyLogFilesNewestFirst()
    {
        $this->writeLog('log-2024-01-01.log', 'x');
        $this->writeLog('log-2024-03-01.log', 'x');
        $this->writeLog('log-2024-02-01.log', 'x');
        $this->writeLog('index.html', 'x');
        $this->writeLog('notes.txt', 'x');

        $this->assertSame(
            ['log-2024-03-01', 'log-2024-02-01', 'log-2024-01-01'],
            $this->store->names(),
        );
    }

    public function testHasOnlyAcceptsExistingValidNames()
    {
        $this->writeLog('log-2024-01-01.log', 'x');
        $this->writeLog('secret.log', 'x');

        $this->assertTrue($this->store->has('log-2024-01-01'));
        $this->assertFalse($this->store->has('log-2024-01-02'));
        $this->assertFalse($this->store->has('secret'));
        $this->assertFalse($this->store->has('../bonfire-logs/log-2024-01-01'));
        $this->assertFalse($this->store->has(''));
    }

    public function testSummaryCountsLevelsAtTheStartOfALineOnly()
    {
        $this->writeLog('log-2024-01-01.log', implode("\n", [
            'INFO - 2024-01-01 10:00:00 --> The ERROR word is just text here',
            'ERROR - 2024-01-01 10:00:01 --> Something broke',
            '#0 stack trace line mentioning CRITICAL',
            'ERROR - 2024-01-01 10:00:02 --> Again',
        ]));

        $this->assertSame(
            '<span class="text-danger">error</span>: 2, <span class="text-info">info</span>: 1',
            $this->store->summary('log-2024-01-01'),
        );
    }

    public function testSummaryOfEmptyFileIsEmpty()
    {
        $this->writeLog('log-2024-01-01.log');

        $this->assertSame('', $this->store->summary('log-2024-01-01'));
    }

    public function testEntriesParsesLevelsAndContinuationLines()
    {
        $this->writeLog('log-2024-01-01.log', implode("\n", [
            'ERROR - 2024-01-01 10:00:01 --> Something broke',
            '#0 stack trace line',
            'INFO - 2024-01-01 10:00:02 --> All fine',
        ]));

        $entries = $this->store->entries('log-2024-01-01');

        $this->assertCount(2, $entries);
        $this->assertSame('ERROR', $entries[0]['level']);
        $this->assertStringContainsString('#0 stack trace line', (string) $entries[0]['extra']);
        $this->assertSame('INFO', $entries[1]['level']);
    }

    public function testEntriesOfEmptyFileIsEmpty()
    {
        $this->writeLog('log-2024-01-01.log');

        $this->assertSame([], $this->store->entries('log-2024-01-01'));
    }

    public function testNeighboursPointToTheAdjacentFiles()
    {
        $this->writeLog('log-2024-01-01.log', 'x');
        $this->writeLog('log-2024-01-02.log', 'x');
        $this->writeLog('log-2024-01-03.log', 'x');

        $middle = $this->store->neighbours('log-2024-01-02');

        $this->assertSame('log-2024-01-01', $middle['prev']['link']);
        $this->assertSame('log-2024-01-03', $middle['next']['link']);
        $this->assertSame('2024-01-02', $middle['curr']['label']);

        $first = $this->store->neighbours('log-2024-01-01');

        $this->assertNull($first['prev']['link']);
        $this->assertSame('log-2024-01-02', $first['next']['link']);
    }

    public function testDeleteRemovesOnlyValidExistingLogs()
    {
        $this->writeLog('log-2024-01-01.log', 'x');
        $this->writeLog('log-2024-01-02.log', 'x');
        $this->writeLog('index.html', 'x');

        $deleted = $this->store->delete(['log-2024-01-01', 'index.html', '../index', 'log-2030-01-01']);

        $this->assertSame(1, $deleted);
        $this->assertFileDoesNotExist($this->dir . 'log-2024-01-01.log');
        $this->assertFileExists($this->dir . 'log-2024-01-02.log');
        $this->assertFileExists($this->dir . 'index.html');
    }

    public function testDeleteAllKeepsNonLogFiles()
    {
        $this->writeLog('log-2024-01-01.log', 'x');
        $this->writeLog('log-2024-01-02.log', 'x');
        $this->writeLog('index.html', 'x');

        $this->assertSame(2, $this->store->deleteAll());
        $this->assertSame([], $this->store->names());
        $this->assertFileExists($this->dir . 'index.html');
    }
}
