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
 * The order and nesting of a structured collection's entries, from trees/collections/<handle>.yaml:
 * a nested list of entry ids.
 */
final readonly class Structure
{
    use TreeFile;

    /**
     * @param list<array{id: string, children: list<mixed>}> $nodes
     */
    public function __construct(public array $nodes)
    {
    }

    /**
     * Where the tree file is, from the content root.
     */
    public static function path(string $handle): string
    {
        return "trees/collections/{$handle}.yaml";
    }

    /**
     * @throws ContentException when the file isn't a nested list of entry ids
     */
    public static function parse(string $raw): self
    {
        if (trim($raw) === '') {
            return new self([]);
        }

        try {
            $data = Yaml::parse($raw);
        } catch (ParseException $e) {
            throw new ContentException("Invalid YAML: {$e->getMessage()}", 0, $e);
        }

        return self::fromData($data);
    }

    /**
     * A tree posted by the editor, which names each entry once.
     *
     * @throws ContentException when the text isn't JSON for a nested list of entry ids
     */
    public static function fromJson(string $json): self
    {
        try {
            $tree = self::fromData(json_decode($json, true, 512, JSON_THROW_ON_ERROR));
        } catch (JsonException) {
            throw new ContentException('The tree is not valid JSON.');
        }

        $ids = $tree->ids();

        if (count($ids) !== count(array_unique($ids))) {
            throw new ContentException('The tree names an entry more than once.');
        }

        return $tree;
    }

    /**
     * @throws ContentException when it isn't a nested list of entries, each `id` a UUID and optionally `children`
     */
    private static function fromData(mixed $data): self
    {
        return new self(self::nodes($data));
    }

    /**
     * Every id the tree names.
     *
     * @return list<string>
     */
    public function ids(): array
    {
        $ids = [];
        self::collectIds($this->nodes, $ids);

        return $ids;
    }

    /**
     * @param list<array{id: string, children: list<mixed>}> $nodes
     * @param list<string>                                   $ids
     */
    private static function collectIds(array $nodes, array &$ids): void
    {
        foreach ($nodes as $node) {
            $ids[] = $node['id'];
            self::collectIds($node['children'], $ids);
        }
    }

    /**
     * The tree holding exactly the entries given, each once. An id that has no entry is dropped and its
     * children move up to where it was, an id named twice stays where it first is, and an entry the tree
     * doesn't name goes last at the root.
     *
     * @param array<string, string> $slugs the slug of each entry, by id
     */
    public function arrange(array $slugs): self
    {
        $seen  = [];
        $nodes = self::keep($this->nodes, $slugs, $seen);

        foreach (array_keys($slugs) as $id) {
            if (! isset($seen[$id])) {
                $nodes[] = ['id' => $id, 'children' => []];
            }
        }

        return new self($nodes);
    }

    /**
     * Where each entry sits: its parent, its position among its siblings from 0, and its URI.
     *
     * @param array<string, string> $slugs the slug of each entry, by id
     *
     * @return array<string, array{parent_id: string|null, sort: int, uri: string|null}>
     */
    public function place(Collection $collection, array $slugs): array
    {
        $placed = [];

        self::walk($this->arrange($slugs)->nodes, null, '', $slugs, $collection, $placed);

        return $placed;
    }

    /**
     * @param list<array{id: string, children: list<mixed>}> $nodes
     * @param array<string, string>                          $slugs
     * @param array<string, true>                            $seen
     *
     * @return list<array{id: string, children: list<mixed>}>
     */
    private static function keep(array $nodes, array $slugs, array &$seen): array
    {
        $kept = [];

        foreach ($nodes as $node) {
            $id = $node['id'];

            if (isset($seen[$id])) {
                continue;
            }

            if (! isset($slugs[$id])) {
                array_push($kept, ...self::keep($node['children'], $slugs, $seen));

                continue;
            }

            $seen[$id] = true;
            $kept[]    = ['id' => $id, 'children' => self::keep($node['children'], $slugs, $seen)];
        }

        return $kept;
    }

    /**
     * @param list<array{id: string, children: list<mixed>}>                            $nodes
     * @param array<string, string>                                                     $slugs
     * @param array<string, array{parent_id: string|null, sort: int, uri: string|null}> $placed
     */
    private static function walk(array $nodes, ?string $parent, string $path, array $slugs, Collection $collection, array &$placed): void
    {
        foreach (array_values($nodes) as $sort => $node) {
            $slugPath = ltrim("{$path}/{$slugs[$node['id']]}", '/');

            $placed[$node['id']] = ['parent_id' => $parent, 'sort' => $sort, 'uri' => $collection->uriFor($slugPath)];

            self::walk($node['children'], $node['id'], $slugPath, $slugs, $collection, $placed);
        }
    }

    /**
     * @return list<array{id: string, children: list<mixed>}>
     *
     * @throws ContentException
     */
    private static function nodes(mixed $data): array
    {
        $data ??= [];

        if (! is_array($data) || ! array_is_list($data)) {
            throw new ContentException('The tree must be a list of entries.');
        }

        $nodes = [];

        foreach ($data as $node) {
            $id = is_array($node) ? ($node['id'] ?? null) : null;

            if (! is_string($id) || preg_match('/^[0-9a-f]{8}(-[0-9a-f]{4}){3}-[0-9a-f]{12}$/i', $id) !== 1) {
                throw new ContentException('Every entry in the tree needs an `id` that is a UUID.');
            }

            $nodes[] = ['id' => strtolower($id), 'children' => self::nodes($node['children'] ?? [])];
        }

        return $nodes;
    }
}
