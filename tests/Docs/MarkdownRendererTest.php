<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Docs;

use Bonfire\Docs\Libraries\MarkdownRenderer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class MarkdownRendererTest extends TestCase
{
    public function testRewritesRelativePageLinksToAdminUrls(): void
    {
        $html = $this->render('[a](named.md) [b](../reference/api.md#the-api) [c](../index.md)');

        $this->assertStringContainsString('href="' . site_url(ADMIN_AREA . '/docs/guide/named') . '"', $html);
        $this->assertStringContainsString('href="' . site_url(ADMIN_AREA . '/docs/reference/api') . '#the-api"', $html);
        $this->assertStringContainsString('href="' . site_url(ADMIN_AREA . '/docs') . '"', $html);
    }

    public function testLeavesOtherLinksAlone(): void
    {
        $html = $this->render('[a](#here) [b](https://example.com/page.md) [c](mailto:a@example.com)');

        $this->assertStringContainsString('href="#here"', $html);
        $this->assertStringContainsString('href="https://example.com/page.md"', $html);
        $this->assertStringContainsString('href="mailto:a@example.com"', $html);
    }

    public function testRemovesUnsafeLinksAndRawHtml(): void
    {
        $html = $this->render("[a](javascript:alert(1))\n\n<div class=\"grid\" onclick=\"x()\">\n\n<script>alert(1)</script>\n\nkept");

        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('<div', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringContainsString('kept', $html);
    }

    #[DataProvider('provideHeadingIdsMatchMkDocs')]
    public function testHeadingIdsMatchMkDocs(string $heading, string $id): void
    {
        $this->assertStringContainsString('id="' . $id . '"', $this->render('## ' . $heading));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideHeadingIdsMatchMkDocs(): iterable
    {
        yield 'symbol between words' => ['Settings → Users', 'settings-users'];

        yield 'inline code' => ['The `personalData` service', 'the-personaldata-service'];

        yield 'punctuation' => ["Bonfire's Defaults", 'bonfires-defaults'];

        yield 'underscores kept' => ['Using `site_url`', 'using-site_url'];
    }

    public function testRepeatedHeadingsGetNumberedIdsLikeMkDocs(): void
    {
        $html = $this->render("## Repeated\n\n## Repeated\n\n## Repeated");

        $this->assertStringContainsString('id="repeated"', $html);
        $this->assertStringContainsString('id="repeated_1"', $html);
        $this->assertStringContainsString('id="repeated_2"', $html);
    }

    public function testAdmonitionsBecomeAlertsWithTheirBodyParsed(): void
    {
        $html = $this->render("!!! warning \"Be careful\"\n    Body **bold**.\n\n    Second.\n\nAfter.");

        $this->assertMatchesRegularExpression(
            '#<div class="alert alert-warning"[^>]*>.*Be careful.*<strong>bold</strong>.*Second\..*</div>\s*<p>After\.</p>#s',
            $html,
        );
    }

    public function testAdmonitionTitleDefaultsToItsTypeAndIsEscaped(): void
    {
        $this->assertStringContainsString('Note', $this->render("!!! note\n    Body."));
        $this->assertStringContainsString('Info', $this->render("!!! info Note\n    Body."));
        $this->assertStringNotContainsString('<b>', $this->render("!!! note \"<b>x</b>\"\n    Body."));
    }

    public function testAdmonitionTitleShowsInlineCode(): void
    {
        $html = $this->render("!!! danger \"`previewInstance()` must <b>not</b>\"\n    Body.");

        $this->assertStringContainsString('<code>previewInstance()</code> must &lt;b&gt;not&lt;/b&gt;', $html);
    }

    public function testRawHtmlInAHeadingStaysOutOfItsId(): void
    {
        $this->assertStringContainsString('id="foo-bar"', $this->render('## Foo <small>bar</small>'));
    }

    public function testAdmonitionSyntaxInsideCodeIsLeftAlone(): void
    {
        $html = $this->render("```php\n!!! warning\n```");

        $this->assertStringNotContainsString('alert', $html);
        $this->assertStringContainsString('!!! warning', $html);
    }

    public function testCodeBlocksKeepTheirLanguageForTheHighlighter(): void
    {
        $this->assertStringContainsString('class="language-php"', $this->render("```php\necho 1;\n```"));
    }

    public function testTablesGetBootstrapStyling(): void
    {
        $this->assertStringContainsString('<table class="table"', $this->render("| a |\n|---|\n| b |"));
    }

    private function render(string $markdown): string
    {
        return (new MarkdownRenderer())->render($markdown, 'guide/setup.md');
    }
}
