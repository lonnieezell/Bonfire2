<?php

namespace Tests\Support\Content;

use Bonfire\Content\Config\Content;
use Bonfire\Content\Indexer;
use Bonfire\Content\ReindexResult;
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

    /**
     * The test's copy of the blueprints in ./blueprints, with a trailing slash.
     */
    protected string $blueprints;

    protected function setUp(): void
    {
        parent::setUp();
        helper('filesystem');

        $this->site       = sys_get_temp_dir() . '/bonfire-content-' . bin2hex(random_bytes(6)) . '/';
        $this->blueprints = sys_get_temp_dir() . '/bonfire-blueprints-' . bin2hex(random_bytes(6)) . '/';
        directory_mirror(__DIR__ . '/site', $this->site);
        directory_mirror(__DIR__ . '/blueprints', $this->blueprints);

        $config                = new Content();
        $config->contentPath   = $this->site;
        $config->blueprintPath = $this->blueprints;
        Factories::injectMock('config', 'Content', $config);
    }

    protected function tearDown(): void
    {
        foreach ([$this->site, $this->blueprints] as $dir) {
            delete_files($dir, true, false, true);
            rmdir($dir);
        }

        Factories::reset('config');

        parent::tearDown();
    }

    /**
     * Gives the blog collection these config lines, and reindexes.
     */
    protected function reindexBlogWith(string $config): ReindexResult
    {
        $this->writeFile('collections/blog.yaml', "title: Blog\ndated: true\n{$config}");

        return (new Indexer($this->site))->reindex();
    }

    /**
     * Writes a file in the test's copy of the site.
     */
    protected function writeFile(string $path, string $contents): void
    {
        file_put_contents($this->site . $path, $contents);
    }
}
