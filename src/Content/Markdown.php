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
     * Converts GitHub Flavored Markdown to HTML, then expands any View Components in it. Expanding comes second so
     * that a component's own indented HTML never becomes a code block.
     *
     * An unknown component throws, except in production, where it is logged and removed.
     */
    public static function toHtml(string $markdown, bool $components = true): string
    {
        $html = (new GithubFlavoredMarkdownConverter(['allow_unsafe_links' => false]))->convert($markdown)->getContent();

        return $components ? (new ComponentRenderer())->render($html, ENVIRONMENT === 'production') : $html;
    }
}
