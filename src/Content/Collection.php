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

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * A collection, from content/collections/<handle>.yaml.
 */
final readonly class Collection
{
    /**
     * The index columns entries can be ordered by.
     */
    public const SORTABLE = ['title', 'slug', 'published_at'];

    /**
     * @param list<string> $blueprints
     */
    public function __construct(
        public string $handle,
        public string $title,
        public bool $dated,
        public string $sortField,
        public string $sortDirection,
        public array $blueprints,
    ) {
    }

    /**
     * @throws ContentException when the file is missing or invalid
     */
    public static function load(string $contentRoot, string $handle): self
    {
        $path = rtrim($contentRoot, '/') . "/collections/{$handle}.yaml";

        // The handle may come from a request, and it's a column of the index.
        if (preg_match('/^[A-Za-z0-9_-]{1,100}$/', $handle) !== 1 || ! is_file($path)) {
            throw new ContentException("Unknown collection: {$handle}");
        }

        try {
            $config = Yaml::parseFile($path);
        } catch (ParseException $e) {
            throw new ContentException("Invalid YAML: {$e->getMessage()}", 0, $e);
        }

        if (! is_array($config)) {
            throw new ContentException('A collection config must be a YAML mapping.');
        }

        $dated = (bool) ($config['dated'] ?? false);

        [$field, $direction] = self::parseSort($config['sort'] ?? ($dated ? 'published_at desc' : 'title asc'));

        return new self(
            $handle,
            is_scalar($config['title'] ?? null) ? (string) $config['title'] : $handle,
            $dated,
            $field,
            $direction,
            array_values(array_filter((array) ($config['blueprints'] ?? []), is_string(...))),
        );
    }

    /**
     * @return array{string, string} the field and the direction
     */
    private static function parseSort(mixed $sort): array
    {
        [$field, $direction] = (is_string($sort) ? explode(' ', trim($sort)) : []) + ['', 'asc'];

        self::assertSort($field, $direction);

        return [$field, $direction];
    }

    /**
     * @throws ContentException when entries can't be ordered this way
     */
    public static function assertSort(string $field, string $direction): void
    {
        if (! in_array($field, self::SORTABLE, true)) {
            throw new ContentException("Can't sort by '{$field}'. Use one of: " . implode(', ', self::SORTABLE));
        }

        if (! in_array(strtolower($direction), ['asc', 'desc'], true)) {
            throw new ContentException("Sort direction must be asc or desc, not '{$direction}'.");
        }
    }
}
