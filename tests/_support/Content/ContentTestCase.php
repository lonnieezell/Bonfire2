<?php

namespace Tests\Support\Content;

use Bonfire\Content\Config\Content;
use CodeIgniter\Config\Factories;
use Tests\Support\TestCase;

/**
 * Runs each test against its own temp-dir copy of the fixture site in ./site.
 *
 * @internal
 */
abstract class ContentTestCase extends TestCase
{
    protected $refresh = true;

    /**
     * The test's copy of the site, with a trailing slash.
     */
    protected string $site;

    protected function setUp(): void
    {
        parent::setUp();
        helper('filesystem');

        $this->site = sys_get_temp_dir() . '/bonfire-content-' . bin2hex(random_bytes(6)) . '/';
        directory_mirror(__DIR__ . '/site', $this->site);

        $config              = new Content();
        $config->contentPath = $this->site;
        Factories::injectMock('config', 'Content', $config);
    }

    protected function tearDown(): void
    {
        delete_files($this->site, true, false, true);
        rmdir($this->site);
        Factories::reset('config');

        parent::tearDown();
    }

    /**
     * Writes a file in the test's copy of the site.
     */
    protected function writeFile(string $path, string $contents): void
    {
        file_put_contents($this->site . $path, $contents);
    }
}
