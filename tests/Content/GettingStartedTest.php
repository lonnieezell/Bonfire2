<?php

namespace Tests\Content;

use Bonfire\Content\Blueprint;
use Bonfire\Content\Field;
use Bonfire\Content\Indexer;
use Bonfire\View\Theme;
use CodeIgniter\I18n\Time;
use Config\Services;
use Tests\Support\Content\ContentTestCase;

/**
 * Follows docs/content/getting_started.md on a site with no content, using the files the page shows.
 *
 * @internal
 */
final class GettingStartedTest extends ContentTestCase
{
    private const DOCS = __DIR__ . '/../../docs/content/';

    private string $themes;

    protected function setUp(): void
    {
        parent::setUp();

        Time::setTestNow('2026-11-04 12:00:00');

        // A fresh install: no collections, no blueprints.
        delete_files($this->site, true, true);
        delete_files($this->blueprints, true, true);

        // The page extends the theme's `master` layout. This one has the section it fills.
        $this->themes = sys_get_temp_dir() . '/bonfire-themes-' . bin2hex(random_bytes(6)) . '/';
        mkdir($this->themes . 'App/blog', 0777, true);
        file_put_contents($this->themes . 'App/master.php', '<main><?= $this->renderSection(\'main\') ?></main>');

        config('Themes')->collections = [$this->themes];
        self::setPrivateProperty(Theme::class, 'themeInfo', []);
        $this->resetRouting();
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        delete_files($this->themes, true, false, true);
        rmdir($this->themes);

        self::setPrivateProperty(Theme::class, 'themeInfo', []);
        $this->resetRouting();

        parent::tearDown();
    }

    public function testAFreshSiteServesAPublishedPostAfterTheStepsInTheGuide(): void
    {
        // Steps 2 to 4.
        $this->createFile($this->site . 'collections/blog.yaml', $this->block('yaml', 0));
        $this->createFile($this->blueprints . 'collections/blog/post.yaml', $this->block('yaml', 1));
        $this->createFile($this->site . 'collections/blog/2026-10-27.hello-world.md', $this->block('markdown', 0));

        $result = (new Indexer($this->site))->reindex();

        $this->assertSame([], $result->errors);
        $this->assertSame(1, $result->added);

        // Step 5.
        $this->assertStringContainsString('public bool $autoRoute = true;', $this->block('php', 0));
        config('Content')->autoRoute = true;

        // Step 6.
        file_put_contents($this->themes . 'App/blog/show.php', $this->block('php', 1));

        $page = $this->get('blog/hello-world');

        $page->assertOK();
        $page->assertSee('Hello world', 'h1');
        $page->assertSee('first', 'em');
    }

    public function testTheBlueprintInTheGuideLoads(): void
    {
        $this->createFile($this->blueprints . 'collections/blog/post.yaml', $this->block('yaml', 1));

        $blueprint = Blueprint::load($this->blueprints, 'blog', 'post');

        $this->assertSame('Post', $blueprint->title);
        $this->assertInstanceOf(Field::class, $blueprint->field('body'));
    }

    /**
     * The nth fenced code block of a language in the guide.
     */
    private function block(string $language, int $n): string
    {
        preg_match_all('/^```' . $language . '\n(.*?)^```$/ms', (string) file_get_contents(self::DOCS . 'getting_started.md'), $matches);

        $this->assertArrayHasKey($n, $matches[1], "The guide has no {$language} block number {$n}.");

        return $matches[1][$n];
    }

    private function createFile(string $path, string $contents): void
    {
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, $contents);
    }

    private function resetRouting(): void
    {
        Services::resetSingle('routes');
        Services::resetSingle('router');
    }
}
