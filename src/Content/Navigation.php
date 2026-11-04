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
 * A navigation menu, from content/navigation/<handle>.yaml. Its items are in trees/navigation/<handle>.yaml.
 */
final readonly class Navigation
{
    /**
     * What a navigation's handle, which is its file name, can be.
     */
    public const HANDLE = '/^[A-Za-z0-9_-]{1,100}$/';

    /**
     * @param list<string> $collections the handles of the collections its items can link to, empty for any
     */
    public function __construct(
        public string $handle,
        public string $title,
        public ?int $maxDepth,
        public array $collections,
    ) {
    }

    /**
     * @throws ContentException when the file is missing or invalid
     */
    public static function load(string $contentRoot, string $handle): self
    {
        $path = rtrim($contentRoot, '/') . "/navigation/{$handle}.yaml";

        // The handle may come from a request, and it's part of a path.
        if (preg_match(self::HANDLE, $handle) !== 1 || ! is_file($path)) {
            throw new ContentException("Unknown navigation: {$handle}");
        }

        try {
            $config = Yaml::parseFile($path);
        } catch (ParseException $e) {
            throw new ContentException("Invalid YAML: {$e->getMessage()}", 0, $e);
        }

        // An empty file is a navigation with nothing set.
        $config ??= [];

        if (! is_array($config) || ($config !== [] && array_is_list($config))) {
            throw new ContentException('A navigation config must be a YAML mapping.');
        }

        $maxDepth = $config['max_depth'] ?? null;

        if ($maxDepth !== null && (! is_int($maxDepth) || $maxDepth < 1)) {
            throw new ContentException('`max_depth` must be a whole number of at least 1.');
        }

        $collections = $config['collections'] ?? [];
        $collections = is_array($collections) ? $collections : [$collections];

        // `collections: pages` names one collection, and must not widen to every collection.
        if ($collections !== array_filter($collections, static fn (mixed $handle): bool => is_string($handle) && $handle !== '')) {
            throw new ContentException('`collections` must be a list of collection handles.');
        }

        return new self(
            $handle,
            is_scalar($config['title'] ?? null) ? (string) $config['title'] : $handle,
            $maxDepth,
            array_values(array_unique($collections)),
        );
    }

    /**
     * Every navigation with a valid config. One that can't be loaded is logged and left out.
     *
     * @return list<self>
     */
    public static function all(string $contentRoot): array
    {
        $navigations = [];

        foreach (glob(rtrim($contentRoot, '/') . '/navigation/*.yaml') ?: [] as $file) {
            try {
                $navigations[] = self::load($contentRoot, basename($file, '.yaml'));
            } catch (ContentException $e) {
                log_message('error', 'Content: navigation {file}: {message}', ['file' => basename($file), 'message' => $e->getMessage()]);
            }
        }

        return $navigations;
    }

    /**
     * Where the tree file is, from the content root.
     */
    public function treePath(): string
    {
        return "trees/navigation/{$this->handle}.yaml";
    }

    /**
     * The tree file as it is now, or an empty string if there is none.
     */
    public function rawTree(string $contentRoot): string
    {
        $file = rtrim($contentRoot, '/') . '/' . $this->treePath();

        return is_file($file) ? (string) file_get_contents($file) : '';
    }
}
