<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Docs\Libraries;

use Bonfire\Docs\Markdown\Admonition;
use Bonfire\Docs\Markdown\AdmonitionRenderer;
use Bonfire\Docs\Markdown\AdmonitionStartParser;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Inline\HtmlInline;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\Table\Table;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\StringContainerInterface;

/**
 * Turns a page of the guide into the HTML to show in the admin.
 */
class MarkdownRenderer
{
    /**
     * The URI of a page, relative to the docs route.
     */
    public static function uriFor(string $path): string
    {
        return $path === 'index.md' ? '' : substr($path, 0, -3);
    }

    /**
     * The path of the admin screen showing a URI.
     */
    public static function adminPath(string $uri): string
    {
        return rtrim(ADMIN_AREA . '/docs/' . $uri, '/');
    }

    /**
     * @param string $markdown The page's source
     * @param string $path     The page's path within docs/, which relative links start from
     */
    public function render(string $markdown, string $path): string
    {
        $environment = new Environment(['html_input' => 'strip', 'allow_unsafe_links' => false]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addBlockStartParser(new AdmonitionStartParser());
        $environment->addRenderer(Admonition::class, new AdmonitionRenderer());
        $environment->addEventListener(DocumentParsedEvent::class, function (DocumentParsedEvent $event) use ($path): void {
            $ids = [];

            foreach ($event->getDocument()->iterator() as $node) {
                if ($node instanceof Link) {
                    $node->setUrl($this->rewriteLink($node->getUrl(), $path));
                } elseif ($node instanceof Heading) {
                    $node->data->set('attributes/id', $this->uniqueId($node, $ids));
                } elseif ($node instanceof Table) {
                    $node->data->set('attributes/class', 'table');
                }
            }
        });

        return (new MarkdownConverter($environment))->convert($markdown)->getContent();
    }

    /**
     * Points a relative link to another page at that page's admin URL. Any other link is left alone.
     */
    private function rewriteLink(string $url, string $path): string
    {
        if (preg_match('/^(?![a-z][a-z0-9+.-]*:|\/)([^#?]+\.md)(#.*)?$/i', $url, $matches) !== 1) {
            return $url;
        }

        $segments = [];

        foreach (explode('/', dirname($path) . '/' . $matches[1]) as $segment) {
            if ($segment === '..') {
                array_pop($segments);
            } elseif ($segment !== '.' && $segment !== '') {
                $segments[] = $segment;
            }
        }

        return site_url(self::adminPath(self::uriFor(implode('/', $segments)))) . ($matches[2] ?? '');
    }

    /**
     * The id MkDocs gives a heading (Python-Markdown's toc slugify and unique), so anchors work in both.
     * The one difference: an accented letter is dropped here, where MkDocs folds it to its base letter,
     * since that takes the intl extension, which Bonfire doesn't require.
     *
     * @param array<string, true> $ids The ids already used on this page
     */
    private function uniqueId(Heading $heading, array &$ids): string
    {
        $text = '';

        foreach ($heading->iterator() as $node) {
            if ($node instanceof StringContainerInterface && ! $node instanceof HtmlInline) {
                $text .= $node->getLiteral();
            }
        }

        $text = strtolower(trim((string) preg_replace('/[^\w\s-]/', '', $text)));
        $base = (string) preg_replace('/[-\s]+/', '-', $text);
        $id   = $base;

        for ($count = 1; $id === '' || isset($ids[$id]); $count++) {
            $id = $base . '_' . $count;
        }

        $ids[$id] = true;

        return $id;
    }
}
