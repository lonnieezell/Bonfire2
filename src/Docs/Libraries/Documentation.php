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

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * The package's user guide, read from its mkdocs.yml and docs/ folder.
 *
 * Pages are addressed by URI, relative to the docs route: '' is the home page,
 * 'modules' the first page of the section whose pages live in docs/modules/,
 * and 'modules/users' the page docs/modules/users.md.
 */
class Documentation
{
    /**
     * The top level of the nav, parsed once.
     *
     * @var list<array{title: string|null, path: string|null, pages: list<array{title: string|null, path: string}>}>|null
     */
    private ?array $nav = null;

    /**
     * @param string $root        The folder holding mkdocs.yml and docs/
     * @param string $environment The environment the site runs in
     */
    public function __construct(private readonly string $root, private readonly string $environment)
    {
    }

    /**
     * The guide never shows in production, nor when its files were left out of a deploy.
     */
    public function isAvailable(): bool
    {
        return $this->environment !== 'production' && $this->nav() !== [];
    }

    /**
     * The page at a URI: its HTML, its title, and the links to show beside it.
     * Only pages listed in the nav can be read, so a URI can never reach another file.
     *
     * @return array{title: string, html: string, nav: list<array{title: string, uri: string}>, uri: string}|null
     */
    public function page(string $uri): ?array
    {
        if (! $this->isAvailable()) {
            return null;
        }

        foreach ($this->nav() as $entry) {
            if ($entry['path'] !== null && MarkdownRenderer::uriFor($entry['path']) === $uri) {
                return $this->build(['title' => $entry['title'], 'path' => $entry['path']], $this->sections());
            }

            foreach ($entry['pages'] as $index => $page) {
                if (MarkdownRenderer::uriFor($page['path']) === $uri || ($index === 0 && $this->sectionUri($entry) === $uri)) {
                    return $this->build($page, $this->links($entry['pages']));
                }
            }
        }

        return null;
    }

    /**
     * The home page and each top-level section, without reading any page.
     *
     * @return list<array{title: string, uri: string}>
     */
    public function sections(): array
    {
        $sections = [];

        foreach ($this->nav() as $entry) {
            $sections[] = [
                'title' => $entry['title'] ?? (string) $entry['path'],
                'uri'   => $entry['path'] === null ? $this->sectionUri($entry) : MarkdownRenderer::uriFor($entry['path']),
            ];
        }

        return $sections;
    }

    /**
     * @param array{title: string|null, path: string}          $page
     * @param list<array{title: string, uri: string}>          $nav
     *
     * @return array{title: string, html: string, nav: list<array{title: string, uri: string}>, uri: string}
     */
    private function build(array $page, array $nav): array
    {
        $markdown = (string) file_get_contents($this->root . 'docs/' . $page['path']);

        return [
            'title' => $page['title'] ?? $this->headingOf($markdown) ?? $page['path'],
            'html'  => (new MarkdownRenderer())->render($markdown, $page['path']),
            'nav'   => $nav,
            'uri'   => MarkdownRenderer::uriFor($page['path']),
        ];
    }

    /**
     * @param list<array{title: string|null, path: string}> $pages
     *
     * @return list<array{title: string, uri: string}>
     */
    private function links(array $pages): array
    {
        $links = [];

        foreach ($pages as $page) {
            $links[] = [
                'title' => $page['title'] ?? $this->headingOf((string) file_get_contents($this->root . 'docs/' . $page['path'])) ?? $page['path'],
                'uri'   => MarkdownRenderer::uriFor($page['path']),
            ];
        }

        return $links;
    }

    private function headingOf(string $markdown): ?string
    {
        return preg_match('/^# +(.+?)\s*$/m', $markdown, $matches) === 1 ? $matches[1] : null;
    }

    /**
     * A section has no URL of its own in mkdocs.yml, so it borrows the folder of its first page.
     *
     * @param array{pages: list<array{title: string|null, path: string}>} $entry
     */
    private function sectionUri(array $entry): string
    {
        $path   = $entry['pages'][0]['path'];
        $folder = dirname($path);

        return $folder === '.' ? MarkdownRenderer::uriFor($path) : $folder;
    }

    /**
     * @return list<array{title: string|null, path: string|null, pages: list<array{title: string|null, path: string}>}>
     */
    private function nav(): array
    {
        if ($this->nav !== null) {
            return $this->nav;
        }

        $this->nav = [];

        if (! is_file($this->root . 'mkdocs.yml')) {
            return $this->nav;
        }

        try {
            $config = Yaml::parseFile($this->root . 'mkdocs.yml');
        } catch (ParseException $e) {
            log_message('error', 'The documentation is hidden, as its mkdocs.yml could not be read: ' . $e->getMessage());

            return $this->nav;
        }

        foreach ($config['nav'] ?? [] as $item) {
            $title = is_array($item) ? (string) array_key_first($item) : null;
            $value = is_array($item) ? reset($item) : $item;

            if (is_array($value)) {
                $pages = $this->flatten($value);

                if ($pages !== []) {
                    $this->nav[] = ['title' => $title, 'path' => null, 'pages' => $pages];
                }
            } elseif (is_file($this->root . 'docs/' . $value)) {
                $this->nav[] = ['title' => $title, 'path' => $value, 'pages' => []];
            }
        }

        return $this->nav;
    }

    /**
     * The pages of a section, in order, with any nested sections folded in.
     *
     * @param array<int, mixed> $items
     *
     * @return list<array{title: string|null, path: string}>
     */
    private function flatten(array $items): array
    {
        $pages = [];

        foreach ($items as $item) {
            $title = is_array($item) ? (string) array_key_first($item) : null;
            $value = is_array($item) ? reset($item) : $item;

            if (is_array($value)) {
                $pages = [...$pages, ...$this->flatten($value)];
            } elseif (is_file($this->root . 'docs/' . $value)) {
                $pages[] = ['title' => $title, 'path' => $value];
            }
        }

        return $pages;
    }
}
