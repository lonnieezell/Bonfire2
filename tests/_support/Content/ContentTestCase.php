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
     * Gives the blog a `tags` taxonomy with the terms php and laravel, and tags the first and second post
     * php, the first one laravel too, and the draft and the future post php. The blueprint gets a tags field.
     *
     * @param string $taxonomyConfig lines for the taxonomy's config
     */
    protected function useTags(string $taxonomyConfig = ''): void
    {
        mkdir($this->site . 'taxonomies/tags', 0777, true);
        $this->writeFile('taxonomies/tags.yaml', "title: Tags\n{$taxonomyConfig}");
        $this->writeFile('taxonomies/tags/php.yaml', "id: aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa\ntitle: PHP\ncolor: blue\n");
        $this->writeFile('taxonomies/tags/laravel.yaml', "id: bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb\ntitle: Laravel\n");
        $this->writeFile('collections/blog.yaml', "title: Blog\ndated: true\nsort: published_at desc\nblueprints:\n  - post\ntaxonomies:\n  - tags\n");

        foreach (['2026-01-10.first-post' => '[php, laravel]', '2026-02-15.second-post' => '[php]', '2026-03-01.draft-post' => '[php]', '2099-01-01.future-post' => '[php]'] as $file => $tags) {
            $path = "collections/blog/{$file}.md";
            $this->writeFile($path, preg_replace('/^(id: .*)$/m', "$1\ntags: {$tags}", (string) file_get_contents($this->site . $path), 1));
        }

        file_put_contents($this->blueprints . 'collections/blog/post.yaml', "  - handle: tags\n    type: terms\n    taxonomy: tags\n", FILE_APPEND);
    }

    /**
     * Writes a file in the test's copy of the site.
     */
    protected function writeFile(string $path, string $contents): void
    {
        file_put_contents($this->site . $path, $contents);
    }
}
