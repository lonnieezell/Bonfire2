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
 * A taxonomy, such as tags, from content/taxonomies/<handle>.yaml. Its terms are the files in content/taxonomies/<handle>/.
 */
final readonly class Taxonomy
{
    /**
     * What a term's slug, which is its file name, can be.
     */
    public const SLUG = '/^[\p{L}\p{N}_-]{1,255}$/u';

    /**
     * What a taxonomy's handle, which is its file name and a front matter key, can be.
     */
    public const HANDLE = '/^[A-Za-z0-9_-]{1,100}$/';

    /**
     * Handles a taxonomy can't have, because an entry already keeps something else under that front matter key.
     */
    public const TAKEN_KEYS = [...Blueprint::RESERVED, 'title', Blueprint::BODY];

    public function __construct(
        public string $handle,
        public string $title,
        public ?string $route,
        public ?string $template,
    ) {
    }

    /**
     * @throws ContentException when the file is missing or invalid
     */
    public static function load(string $contentRoot, string $handle): self
    {
        $path = rtrim($contentRoot, '/') . "/taxonomies/{$handle}.yaml";

        // The handle may come from a request, and it's a column of the index.
        if (preg_match(self::HANDLE, $handle) !== 1 || ! is_file($path)) {
            throw new ContentException("Unknown taxonomy: {$handle}");
        }

        try {
            $config = Yaml::parseFile($path);
        } catch (ParseException $e) {
            throw new ContentException("Invalid YAML: {$e->getMessage()}", 0, $e);
        }

        // An empty file is a taxonomy with nothing set.
        $config ??= [];

        if (! is_array($config)) {
            throw new ContentException('A taxonomy config must be a YAML mapping.');
        }

        $route = Collection::stringOption($config, 'route');

        // Without a slug, every term would get the same URI.
        if ($route !== null && (! str_contains($route, '{slug}') || preg_match('/[{}]/', str_replace('{slug}', '', $route)) === 1)) {
            throw new ContentException('`route` must contain {slug}, and no other placeholder.');
        }

        return new self(
            $handle,
            is_scalar($config['title'] ?? null) ? (string) $config['title'] : $handle,
            $route,
            Collection::stringOption($config, 'template'),
        );
    }

    /**
     * Every taxonomy with a valid config. One that can't be loaded is logged and left out.
     *
     * @return list<self>
     */
    public static function all(string $contentRoot): array
    {
        $taxonomies = [];

        foreach (glob(rtrim($contentRoot, '/') . '/taxonomies/*.yaml') ?: [] as $file) {
            try {
                $taxonomies[] = self::load($contentRoot, basename($file, '.yaml'));
            } catch (ContentException $e) {
                log_message('error', 'Content: taxonomy {file}: {message}', ['file' => basename($file), 'message' => $e->getMessage()]);
            }
        }

        return $taxonomies;
    }

    /**
     * The slug of the term whose page is at this URI, or null if the URI isn't one of the taxonomy's.
     */
    public function slugAt(string $uri): ?string
    {
        if ($this->route === null) {
            return null;
        }

        $pattern = '#^' . str_replace('\{slug\}', '([^/]+)', preg_quote(Collection::normalizeUri($this->route), '#')) . '$#';

        return preg_match($pattern, Collection::normalizeUri($uri), $matches) === 1 && preg_match(self::SLUG, $matches[1]) === 1 ? $matches[1] : null;
    }
}
