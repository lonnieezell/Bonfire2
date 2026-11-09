<?php

namespace Tests\Commands;

use Bonfire\Content\Blueprint;
use Bonfire\Content\Entry;
use Bonfire\Content\Indexer;
use CodeIgniter\Test\StreamFilterTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Content\ContentTestCase;

/**
 * @internal
 */
final class BlockGeneratorTest extends ContentTestCase
{
    use StreamFilterTrait;

    /**
     * A view folder of its own, as tests/_support/Views/blocks has the partials other tests use.
     */
    private const FOLDER = 'made_blocks';

    private const VIEWS = SUPPORTPATH . 'Views/' . self::FOLDER . '/';
    private const PAGE  = '11111111-1111-4111-8111-111111111111';
    private const SALE  = 'f0000000-0000-4000-8000-000000000001';

    protected function setUp(): void
    {
        parent::setUp();

        config('Content')->blockViewPath = self::FOLDER;
    }

    protected function tearDown(): void
    {
        if (is_dir(self::VIEWS)) {
            delete_files(self::VIEWS, true, false, true);
            rmdir(self::VIEWS);
        }

        parent::tearDown();
    }

    public function testTheNewBlockRendersOnAPage(): void
    {
        command('make:block promo');

        $this->assertSame('Promo', Blueprint::loadBlock($this->blueprints, 'promo')->title);

        $this->usePage("sections:\n  - type: promo\n    heading: Big <news>");

        $this->assertStringContainsString('Big &lt;news&gt;', $this->page()->renderBlocks('sections'));
    }

    public function testAnExistingFileIsKeptAndNamedWhileTheOtherIsWritten(): void
    {
        mkdir($this->blueprints . 'blocks');
        file_put_contents($this->blueprints . 'blocks/promo.yaml', 'mine');

        command('make:block promo');

        $this->assertSame('mine', file_get_contents($this->blueprints . 'blocks/promo.yaml'));
        $this->assertFileExists(self::VIEWS . 'promo.php');
        $this->assertStringContainsString('blocks/promo.yaml', $this->getStreamFilterBuffer());
    }

    public function testRunningItAgainKeepsBothFilesAndNamesThem(): void
    {
        command('make:block promo');
        file_put_contents(self::VIEWS . 'promo.php', 'mine');
        file_put_contents($this->blueprints . 'blocks/promo.yaml', 'mine too');
        $this->resetStreamFilterBuffer();

        command('make:block promo');

        $this->assertSame('mine', file_get_contents(self::VIEWS . 'promo.php'));
        $this->assertSame('mine too', file_get_contents($this->blueprints . 'blocks/promo.yaml'));
        $this->assertMatchesRegularExpression('#Skipped.*blocks/promo\.yaml#', $this->getStreamFilterBuffer());
        $this->assertMatchesRegularExpression('#Skipped.*' . self::FOLDER . '/promo\.php#', $this->getStreamFilterBuffer());
    }

    #[DataProvider('provideAnInvalidTypeIsAnErrorThatNamesItAndWritesNothing')]
    public function testAnInvalidTypeIsAnErrorThatNamesItAndWritesNothing(string $type): void
    {
        command("make:block {$type}");

        $this->assertStringContainsString($type, $this->getStreamFilterBuffer());
        $this->assertDirectoryDoesNotExist($this->blueprints . 'blocks');
        $this->assertDirectoryDoesNotExist(self::VIEWS);
        $this->assertFileDoesNotExist($this->blueprints . 'evil.yaml');
        $this->assertFileDoesNotExist(SUPPORTPATH . 'Views/evil.php');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideAnInvalidTypeIsAnErrorThatNamesItAndWritesNothing(): iterable
    {
        yield 'a path' => ['../evil'];

        yield 'a folder' => ['sub/evil'];

        yield 'a dot' => ['evil.php'];

        yield 'too long' => [str_repeat('a', 101)];
    }

    public function testNoTypeIsAnErrorAndWritesNothing(): void
    {
        command('make:block');

        $this->assertNotSame('', trim($this->getStreamFilterBuffer()));
        $this->assertDirectoryDoesNotExist($this->blueprints . 'blocks');
        $this->assertDirectoryDoesNotExist(self::VIEWS);
    }

    public function testTheFragmentBlockGetsAPartialThatRendersTheFragmentsSections(): void
    {
        command('make:block promo');
        command('make:block fragment');

        $this->writeFile('collections/promos.yaml', "title: Promos\nfragments: true\n");
        mkdir($this->site . 'collections/promos');
        $this->writeFile('collections/promos/spring-sale.md', "---\nid: " . self::SALE . "\ntitle: Spring sale\nsections:\n  - type: promo\n    heading: Half price\n---\n");
        $this->usePage("sections:\n  - type: fragment\n    fragment: [" . self::SALE . ']');

        $this->assertStringContainsString('Half price', $this->page()->renderBlocks('sections'));
    }

    #[DataProvider('provideTheFragmentBlockKeepsItsBuiltInBlueprint')]
    public function testTheFragmentBlockKeepsItsBuiltInBlueprint(string $type): void
    {
        command("make:block {$type}");

        $this->assertFileExists(self::VIEWS . 'fragment.php');
        $this->assertDirectoryDoesNotExist($this->blueprints . 'blocks');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideTheFragmentBlockKeepsItsBuiltInBlueprint(): iterable
    {
        yield 'its own name' => ['fragment'];

        yield 'another case' => ['Fragment'];
    }

    public function testATrailingNewlineIsNotAValidType(): void
    {
        $this->assertDoesNotMatchRegularExpression(Blueprint::NAME, "hero\n");
    }

    /**
     * Gives the post blueprint a `sections` blocks field and the first post these front matter lines, and reindexes.
     */
    private function usePage(string $frontMatter): void
    {
        file_put_contents(
            $this->blueprints . 'collections/blog/post.yaml',
            "title: Post\nfields:\n  - handle: title\n    type: text\n  - handle: slug\n    type: slug\n  - handle: sections\n    type: blocks\n",
        );
        $this->writeFile('collections/blog/2026-01-10.first-post.md', "---\nid: " . self::PAGE . "\ntitle: First post\n{$frontMatter}\n---\nThe first post.\n");
        (new Indexer($this->site))->reindex();
    }

    private function page(): Entry
    {
        $entry = content()->find(self::PAGE);
        $this->assertInstanceOf(Entry::class, $entry);

        return $entry;
    }
}
