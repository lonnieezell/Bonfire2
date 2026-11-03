<?php

namespace Tests\Commands;

use CodeIgniter\Test\StreamFilterTrait;
use Tests\Support\Content\ContentTestCase;

/**
 * @internal
 */
final class ContentReindexTest extends ContentTestCase
{
    use StreamFilterTrait;

    public function testIndexesTheSiteAndReportsWhatItDid(): void
    {
        command('content:reindex');

        $this->seeNumRecords(4, 'content_entries', []);
        $this->assertStringContainsString('4 added, 0 updated, 0 removed, 0 unchanged', $this->getStreamFilterBuffer());
    }

    public function testFreshRebuildsTheIndex(): void
    {
        command('content:reindex');
        $this->resetStreamFilterBuffer();

        command('content:reindex --fresh');

        $this->seeNumRecords(4, 'content_entries', []);
        $this->assertStringContainsString('4 added, 0 updated, 0 removed, 0 unchanged', $this->getStreamFilterBuffer());
    }

    public function testAFailureSaysSoInsteadOfReportingCounts(): void
    {
        config('Content')->contentPath = $this->site . 'not-mounted/';

        command('content:reindex');

        $this->assertStringContainsString('not-mounted', $this->getStreamFilterBuffer());
        $this->assertStringNotContainsString('added', $this->getStreamFilterBuffer());
    }

    public function testReportsAFileItCantIndexWithoutStopping(): void
    {
        $this->writeFile('collections/blog/broken.md', "---\ntitle: No id\n---\n");

        command('content:reindex');

        $this->seeNumRecords(4, 'content_entries', []);
        $this->assertStringContainsString('collections/blog/broken.md', $this->getStreamFilterBuffer());
    }
}
