<?php

namespace Tests\Content;

use Bonfire\Content\Entry;
use Bonfire\Content\Indexer;
use Bonfire\View\Decorator;
use RuntimeException;
use Tests\Support\Content\ContentTestCase;

/**
 * @internal
 */
final class MarkdownTest extends ContentTestCase
{
    private const FIRST_POST = '11111111-1111-4111-8111-111111111111';

    protected function setUp(): void
    {
        parent::setUp();

        config('Themes')->componentsLookupPaths = [SUPPORTPATH . 'Views/Components/'];
    }

    public function testHelperRendersMarkdown(): void
    {
        $this->assertSame("<p>Hello <strong>there</strong></p>\n", content_markdown('Hello **there**'));
    }

    public function testHelperExpandsComponents(): void
    {
        $this->assertStringContainsString('Hello Ada', content_markdown('<x-greeting name="Ada" />'));
        $this->assertStringNotContainsString('<x-greeting', content_markdown('<x-greeting name="Ada" />'));
    }

    public function testHelperCanLeaveComponentsAlone(): void
    {
        $this->assertStringContainsString('<x-greeting name="Ada" />', content_markdown('<x-greeting name="Ada" />', components: false));
    }

    public function testMarkdownInsideAPairedComponentFollowsTheBlankLineRule(): void
    {
        // Blank lines around the content make it Markdown, so it ends up in the slot as HTML.
        $this->assertSame("[\n<p>Some <strong>bold</strong> text</p>\n]\n", content_markdown("<x-wrapper>\n\nSome **bold** text\n\n</x-wrapper>"));

        // Without them the whole block is raw HTML and the Markdown stays as written.
        $this->assertStringContainsString('[Some **bold** text]', str_replace("\n", '', content_markdown("<x-wrapper>\nSome **bold** text\n</x-wrapper>")));
    }

    public function testComponentsInACodeBlockAreNotExpanded(): void
    {
        $html = content_markdown("Intro\n\n<x-greeting name=\"Ada\" />\n\n    <x-greeting name=\"Bob\" />\n");

        $this->assertStringContainsString('Hello Ada', $html);
        $this->assertStringContainsString('<pre><code>&lt;x-greeting name=&quot;Bob&quot; /&gt;', $html);
        $this->assertStringNotContainsString('Hello Bob', $html);
    }

    public function testAComponentWithIndentedHtmlIsNotACodeBlock(): void
    {
        // The indented lines come from the component's template, so they're added after the conversion.
        $this->writeComponent('indented', "<div>\n    <p>Indented</p>\n</div>\n");

        $html = content_markdown("<x-indented />\n");

        $this->assertStringContainsString("    <p>Indented</p>\n", $html);
        $this->assertStringNotContainsString('<pre>', $html);
    }

    public function testExpandedOutputIsUnchangedByTheDecorator(): void
    {
        $html = content_markdown("<x-wrapper>\n\nSome **bold** text\n\n</x-wrapper>\n\n<x-greeting name=\"Ada\" />");

        $this->assertSame($html, Decorator::decorate($html));
    }

    public function testAnUnknownComponentThrowsOutsideProduction(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('View not found for component: nope');

        content_markdown('<x-nope />');
    }

    public function testStyleAndScriptStayEscaped(): void
    {
        $html = content_markdown("<style>body { display: none }</style>\n\n<script>alert(1)</script>");

        $this->assertStringContainsString('&lt;style>', $html);
        $this->assertStringContainsString('&lt;script>', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<style>', $html);
    }

    public function testEntryContentExpandsComponentsOutsideAView(): void
    {
        $entry = $this->entryWithBody("Hi\n\n<x-greeting name=\"Ada\" />\n");

        $this->assertStringContainsString('Hello Ada', $entry->content());
    }

    public function testEntryContentCanSkipComponents(): void
    {
        $entry = $this->entryWithBody("Hi\n\n<x-greeting name=\"Ada\" />\n");

        $html = $entry->content(components: false);

        $this->assertStringContainsString('<x-greeting name="Ada" />', $html);
        $this->assertStringNotContainsString('Hello Ada', $html);
    }

    public function testEntryContentThrowsForAnUnknownComponent(): void
    {
        $entry = $this->entryWithBody("<x-nope />\n");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('nope');

        $entry->content();
    }

    public function testSeoDescriptionIgnoresComponents(): void
    {
        $entry = $this->entryWithBody("<x-nope />\n\nThe first paragraph.\n");

        $this->assertSame('The first paragraph.', $entry->seo()['description']);
    }

    private function entryWithBody(string $body): Entry
    {
        $this->writeFile('collections/blog/2026-01-10.first-post.md', "---\nid: " . self::FIRST_POST . "\n---\n" . $body);
        (new Indexer($this->site))->reindex();

        $entry = content()->find(self::FIRST_POST);
        $this->assertInstanceOf(Entry::class, $entry);

        return $entry;
    }

    private function writeComponent(string $name, string $template): void
    {
        $dir = $this->site . 'components/';
        if (! is_dir($dir)) {
            mkdir($dir);
        }

        file_put_contents($dir . $name . '.php', $template);
        config('Themes')->componentsLookupPaths = [$dir, SUPPORTPATH . 'Views/Components/'];
    }
}
