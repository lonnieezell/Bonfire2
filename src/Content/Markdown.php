<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Content;

use Bonfire\View\ComponentRenderer;
use League\CommonMark\GithubFlavoredMarkdownConverter;

/**
 * The Markdown pipeline behind `Entry::content()` and `content_markdown()`.
 */
final class Markdown
{
    /**
     * Kept because indexing converts every string of every entry, and building one costs far more than a conversion.
     */
    private static ?GithubFlavoredMarkdownConverter $converter = null;

    /**
     * Converts GitHub Flavored Markdown to HTML, then expands any View Components in it. Expanding comes second so
     * that a component's own indented HTML never becomes a code block.
     *
     * An unknown component throws, except in production, where it is logged and removed.
     */
    public static function toHtml(string $markdown, bool $components = true): string
    {
        self::$converter ??= new GithubFlavoredMarkdownConverter(['allow_unsafe_links' => false]);

        $html = self::$converter->convert($markdown)->getContent();

        return $components ? (new ComponentRenderer())->render($html, ENVIRONMENT === 'production') : $html;
    }

    /**
     * Removes the markup from HTML. Whitespace is collapsed, so a line the author wrapped reads as one.
     */
    public static function htmlToText(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }
}
