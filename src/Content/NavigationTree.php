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

use JsonException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * The links of a menu, from trees/navigation/<handle>.yaml: a nested list where each item links to an entry
 * by its id, with an optional title of its own, or has a `url` and a `title`.
 */
final readonly class NavigationTree
{
    use TreeFile;

    /**
     * What a link's URL can be, as a pattern for an HTML input too. It ends up in an href, where a javascript: URL would run.
     */
    public const URL_PATTERN = '(https?:|mailto:|tel:|/|#|\?).*';

    /**
     * @param list<array{entry?: string, url?: string, title?: string, children: list<mixed>}> $nodes
     */
    public function __construct(public array $nodes)
    {
    }

    /**
     * Where the tree file is, from the content root.
     */
    public static function path(string $handle): string
    {
        return "trees/navigation/{$handle}.yaml";
    }

    /**
     * @throws ContentException when the file isn't a nested list of valid items
     */
    public static function parse(string $raw): self
    {
        try {
            return new self(self::nodes(Yaml::parse($raw)));
        } catch (ParseException $e) {
            throw new ContentException("Invalid YAML: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * A tree posted by the editor.
     *
     * @throws ContentException when the text isn't JSON for a nested list of valid items
     */
    public static function fromJson(string $json): self
    {
        try {
            return new self(self::nodes(json_decode($json, true, 512, JSON_THROW_ON_ERROR)));
        } catch (JsonException) {
            throw new ContentException('The menu is not valid JSON.');
        }
    }

    /**
     * The id of every entry the tree links to.
     *
     * @return list<string>
     */
    public function entryIds(): array
    {
        $ids   = [];
        $nodes = $this->nodes;

        array_walk_recursive($nodes, static function (mixed $value, int|string $key) use (&$ids): void {
            if ($key === 'entry') {
                $ids[] = $value;
            }
        });

        return array_values(array_unique($ids));
    }

    /**
     * The menu as a site shows it. An item whose entry isn't given is left out, with the items below it.
     *
     * @param array<string, array<string, mixed>> $entries the index row of each entry that can be shown, by id
     * @param string                              $current the URI being shown, in the form URIs have in the index
     *
     * @return list<NavigationItem>
     */
    public function items(array $entries, string $current): array
    {
        $items = [];

        foreach ($this->nodes as $node) {
            $entry = $entries[$node['entry'] ?? ''] ?? null;
            $url   = $entry['uri'] ?? $node['url'] ?? null;

            if ($url === null) {
                continue;
            }

            // A path on this site, and not //host/path.
            $local = str_starts_with($url, '/') && ! str_starts_with($url, '//');
            $path  = parse_url($url, PHP_URL_PATH);

            $items[] = new NavigationItem(
                $node['title'] ?? $entry['title'],
                $local ? site_url($url) : $url,
                $local && is_string($path) && Collection::normalizeUri(rawurldecode($path)) === $current,
                (new self($node['children']))->items($entries, $current),
            );
        }

        return $items;
    }

    /**
     * @return list<array{entry?: string, url?: string, title?: string, children: list<mixed>}>
     *
     * @throws ContentException
     */
    private static function nodes(mixed $data): array
    {
        $data ??= [];

        if (! is_array($data) || ! array_is_list($data)) {
            throw new ContentException('The menu must be a list of items.');
        }

        return array_map(self::node(...), $data);
    }

    /**
     * @return array{entry?: string, url?: string, title?: string, children: list<mixed>}
     *
     * @throws ContentException
     */
    private static function node(mixed $node): array
    {
        if (! is_array($node) || isset($node['entry']) === isset($node['url'])) {
            throw new ContentException('Every item in the menu needs either an `entry` or a `url`.');
        }

        $title = $node['title'] ?? '';

        // Unquoted in YAML, a title like 2024 is a number.
        if (! is_scalar($title)) {
            throw new ContentException('An item\'s `title` must be text.');
        }

        $title    = trim((string) $title);
        $children = self::nodes($node['children'] ?? []);

        if (isset($node['entry'])) {
            if (! ContentFiles::isUuid($node['entry'])) {
                throw new ContentException('An item\'s `entry` must be a UUID.');
            }

            return ['entry' => strtolower($node['entry']), ...($title === '' ? [] : ['title' => $title]), 'children' => $children];
        }

        if ($title === '') {
            throw new ContentException('A link needs a title.');
        }

        if (! is_string($node['url']) || preg_match('~^' . self::URL_PATTERN . '$~is', $node['url']) !== 1) {
            throw new ContentException('A link\'s URL must start with /, #, ?, http:, https:, mailto: or tel:.');
        }

        return ['url' => $node['url'], 'title' => $title, 'children' => $children];
    }
}
