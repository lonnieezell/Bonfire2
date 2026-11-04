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
        public ?string $route,
        public ?string $template,
        public ?string $controller,
        public bool $structure = false,
        public ?int $maxDepth = null,
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

        $route      = self::stringOption($config, 'route');
        $template   = self::stringOption($config, 'template');
        $controller = self::stringOption($config, 'controller');

        self::assertRouting($route, $template, $controller);

        $structure = $config['structure'] ?? false;
        $maxDepth  = $config['max_depth'] ?? null;

        if (! is_bool($structure)) {
            throw new ContentException('`structure` must be true or false.');
        }

        if ($maxDepth !== null && (! is_int($maxDepth) || $maxDepth < 1)) {
            throw new ContentException('`max_depth` must be a whole number of at least 1.');
        }

        return new self(
            $handle,
            is_scalar($config['title'] ?? null) ? (string) $config['title'] : $handle,
            $dated,
            $field,
            $direction,
            array_values(array_filter((array) ($config['blueprints'] ?? []), is_string(...))),
            $route,
            $template,
            $controller,
            $structure,
            $maxDepth,
        );
    }

    /**
     * Every collection with a valid config, by handle. One that can't be loaded is logged and left out.
     *
     * @return list<self>
     */
    public static function all(string $contentRoot): array
    {
        $collections = [];

        foreach (glob(rtrim($contentRoot, '/') . '/collections/*.yaml') ?: [] as $file) {
            try {
                $collections[] = self::load($contentRoot, basename($file, '.yaml'));
            } catch (ContentException $e) {
                log_message('error', 'Content: collection {file}: {message}', ['file' => basename($file), 'message' => $e->getMessage()]);
            }
        }

        return $collections;
    }

    /**
     * The URI of an entry with this slug, or null if the collection has no route. In a structured collection
     * the slug is the entry's whole path, such as `about/team`.
     */
    public function uriFor(string $slug): ?string
    {
        return $this->route === null ? null : self::normalizeUri(str_replace('{slug}', $slug, $this->route));
    }

    /**
     * The index column holds 255 characters. The parser checks the URI of an entry in a plain collection, but
     * a structured collection's URIs come from the tree, so those that set them check here.
     *
     * @throws ContentException when the URI wouldn't fit
     */
    public static function assertUriFits(?string $uri): void
    {
        if (mb_strlen((string) $uri) > 255) {
            throw new ContentException('The URI is longer than 255 characters.');
        }
    }

    /**
     * The form URIs have in the index.
     */
    public static function normalizeUri(string $uri): string
    {
        return '/' . trim($uri, '/');
    }

    /**
     * @param array<array-key, mixed> $config
     *
     * @throws ContentException when the option is set to anything but a string
     */
    private static function stringOption(array $config, string $key): ?string
    {
        $value = $config[$key] ?? null;

        if ($value !== null && (! is_string($value) || $value === '')) {
            throw new ContentException("`{$key}` must be a string.");
        }

        return $value;
    }

    /**
     * @throws ContentException when the collection can't be routed this way
     */
    private static function assertRouting(?string $route, ?string $template, ?string $controller): void
    {
        // Without a slug, every entry would get the same URI.
        if ($route !== null && (! str_contains($route, '{slug}') || preg_match('/[{}]/', str_replace('{slug}', '', $route)) === 1)) {
            throw new ContentException('`route` must contain {slug}, and no other placeholder.');
        }

        if ($template !== null && $controller !== null) {
            throw new ContentException('A collection can declare a `template` or a `controller`, not both.');
        }

        if ($controller !== null && preg_match('/^[\w\\\\]+::\w+$/', $controller) !== 1) {
            throw new ContentException('`controller` must look like App\\Controllers\\Blog::show.');
        }
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
