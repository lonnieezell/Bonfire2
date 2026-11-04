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
 * The items of a navigation menu, from trees/navigation/<handle>.yaml: a nested list where each item is
 * `entry: <uuid>` with an optional `title`, or `url` and `title`, and optionally `children`.
 *
 * @phpstan-type Node array{entry: string|null, url: string|null, title: string|null, children: list<mixed>}
 */
final readonly class NavigationTree
{
    /**
     * What a link's URL can start with: a path on the site, a fragment, a query, or a scheme that is safe to link to.
     */
    private const URL = '#^(/(?!/)|\#|\?|https?://|mailto:|tel:)#i';

    /**
     * @param list<Node> $nodes
     */
    public function __construct(public array $nodes)
    {
    }

    /**
     * What the editor posts back: a hash of the file it was opened on. A missing file hashes as an empty one.
     */
    public static function hash(string $raw): string
    {
        return hash('sha256', $raw);
    }

    /**
     * @throws ContentException when the file isn't a nested list of entry and link items
     */
    public static function parse(string $raw): self
    {
        if (trim($raw) === '') {
            return new self([]);
        }

        try {
            return new self(self::nodes(Yaml::parse($raw)));
        } catch (ParseException $e) {
            throw new ContentException("Invalid YAML: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * A tree posted by the editor.
     *
     * @throws ContentException when the text isn't JSON for a nested list of entry and link items
     */
    public static function fromJson(string $json): self
    {
        try {
            return new self(self::nodes(json_decode($json, true, 512, JSON_THROW_ON_ERROR)));
        } catch (JsonException) {
            throw new ContentException('The tree is not valid JSON.');
        }
    }

    public function toYaml(): string
    {
        return Yaml::dump(self::forFile($this->nodes), 99, 2);
    }

    /**
     * How many levels deep the tree goes. The root is the first.
     */
    public function depth(): int
    {
        return self::levels($this->nodes);
    }

    /**
     * Every entry id the tree names.
     *
     * @return list<string>
     */
    public function entryIds(): array
    {
        $ids = [];
        self::collectIds($this->nodes, $ids);

        return array_values(array_unique($ids));
    }

    /**
     * The tree without the items for entries that aren't in `$ids`. The items below such an item move up to where it was.
     *
     * @param list<string> $ids
     */
    public function onlyEntries(array $ids): self
    {
        return new self(self::keep($this->nodes, $ids));
    }

    /**
     * The menu as the front end gets it. An item for an entry takes its title and URL from the entry, unless
     * it has a title of its own. An item for an entry that isn't in `$entries` is left out, with the items below it.
     *
     * @param array<string, array<string, mixed>> $entries     the published entries' index rows, by id
     * @param string                              $currentPath the path of the page being shown
     *
     * @return list<array{title: string, url: string|null, active: bool, children: list<mixed>}>
     */
    public function items(array $entries, string $currentPath): array
    {
        return self::build($this->nodes, $entries, Collection::normalizeUri($currentPath));
    }

    /**
     * @param list<Node>                          $nodes
     * @param array<string, array<string, mixed>> $entries
     *
     * @return list<array{title: string, url: string|null, active: bool, children: list<mixed>}>
     */
    private static function build(array $nodes, array $entries, string $currentPath): array
    {
        $items = [];

        foreach ($nodes as $node) {
            if ($node['entry'] !== null) {
                if (! isset($entries[$node['entry']])) {
                    continue;
                }

                $title = $node['title'] ?? $entries[$node['entry']]['title'];
                $url   = $entries[$node['entry']]['uri'];
            } else {
                $title = $node['title'];
                $url   = $node['url'];
            }

            $items[] = [
                'title' => (string) $title,
                'url'   => $url,
                // A link to another site is never the current page.
                'active'   => $url !== null && str_starts_with($url, '/') && Collection::normalizeUri(strtok($url, '?#') ?: '/') === $currentPath,
                'children' => self::build($node['children'], $entries, $currentPath),
            ];
        }

        return $items;
    }

    /**
     * @param list<Node>   $nodes
     * @param list<string> $ids
     *
     * @return list<Node>
     */
    private static function keep(array $nodes, array $ids): array
    {
        $kept = [];

        foreach ($nodes as $node) {
            $children = self::keep($node['children'], $ids);

            if ($node['entry'] !== null && ! in_array($node['entry'], $ids, true)) {
                array_push($kept, ...$children);

                continue;
            }

            $kept[] = [...$node, 'children' => $children];
        }

        return $kept;
    }

    /**
     * @param list<Node>   $nodes
     * @param list<string> $ids
     */
    private static function collectIds(array $nodes, array &$ids): void
    {
        foreach ($nodes as $node) {
            if ($node['entry'] !== null) {
                $ids[] = $node['entry'];
            }

            self::collectIds($node['children'], $ids);
        }
    }

    /**
     * @return list<Node>
     *
     * @throws ContentException
     */
    private static function nodes(mixed $data): array
    {
        $data ??= [];

        if (! is_array($data) || ! array_is_list($data)) {
            throw new ContentException('The tree must be a list of items.');
        }

        $nodes = [];

        foreach ($data as $item) {
            if (! is_array($item)) {
                throw new ContentException('Every item in the tree must be an entry or a link.');
            }

            $entry = $item['entry'] ?? null;
            $url   = $item['url'] ?? null;
            $title = $item['title'] ?? null;

            if (($entry === null) === ($url === null)) {
                throw new ContentException('Every item in the tree needs an `entry` or a `url`, and not both.');
            }

            if ($title !== null && ! is_string($title)) {
                throw new ContentException('The `title` of an item must be text.');
            }

            // An empty title is none, which is how the editor posts an entry's title left alone.
            $title = $title === null || trim($title) === '' ? null : trim($title);

            if ($entry !== null && ! ContentFiles::isUuid($entry)) {
                throw new ContentException('The `entry` of an item must be an entry id.');
            }

            if ($url !== null) {
                if (! is_string($url) || preg_match(self::URL, $url) !== 1 || preg_match('/[\x00-\x20\x7f\\\\]/', $url) === 1) {
                    throw new ContentException('The `url` of a link must be a path on the site, or start with http://, https://, mailto: or tel:.');
                }

                if ($title === null) {
                    throw new ContentException('A link needs a `title`.');
                }
            }

            $nodes[] = [
                'entry'    => $entry === null ? null : strtolower($entry),
                'url'      => $url,
                'title'    => $title,
                'children' => self::nodes($item['children'] ?? []),
            ];
        }

        return $nodes;
    }

    /**
     * @param list<Node> $nodes
     */
    private static function levels(array $nodes): int
    {
        $deepest = 0;

        foreach ($nodes as $node) {
            $deepest = max($deepest, self::levels($node['children']));
        }

        return $nodes === [] ? 0 : $deepest + 1;
    }

    /**
     * The nodes as the file holds them, without empty `children` or an unset `title`.
     *
     * @param list<Node> $nodes
     *
     * @return list<array<string, mixed>>
     */
    private static function forFile(array $nodes): array
    {
        return array_map(static function (array $node): array {
            $item = $node['entry'] !== null ? ['entry' => $node['entry']] : ['url' => $node['url']];

            if ($node['title'] !== null) {
                $item['title'] = $node['title'];
            }

            if ($node['children'] !== []) {
                $item['children'] = self::forFile($node['children']);
            }

            return $item;
        }, $nodes);
    }
}
