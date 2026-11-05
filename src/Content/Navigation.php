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
 * A menu, from content/navigation/<handle>.yaml. Its links are in its tree, at trees/navigation/<handle>.yaml.
 */
final readonly class Navigation
{
    /**
     * @param list<string> $collections the handles of the collections whose entries it can link to
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

        // The handle may come from a request.
        if (preg_match('/^[A-Za-z0-9_-]{1,100}$/', $handle) !== 1 || ! is_file($path)) {
            throw new ContentException("Unknown navigation: {$handle}");
        }

        try {
            $config = Yaml::parseFile($path);
        } catch (ParseException $e) {
            throw new ContentException("Invalid YAML: {$e->getMessage()}", 0, $e);
        }

        // An empty file is a menu with nothing set.
        $config ??= [];

        if (! is_array($config)) {
            throw new ContentException('A navigation config must be a YAML mapping.');
        }

        $maxDepth = $config['max_depth'] ?? null;

        if ($maxDepth !== null && (! is_int($maxDepth) || $maxDepth < 1)) {
            throw new ContentException('`max_depth` must be a whole number of at least 1.');
        }

        return new self(
            $handle,
            is_scalar($config['title'] ?? null) ? (string) $config['title'] : $handle,
            $maxDepth,
            array_values(array_unique(array_filter((array) ($config['collections'] ?? []), is_string(...)))),
        );
    }

    /**
     * Every menu with a valid config, by handle. One that can't be loaded is logged and left out.
     *
     * @return list<self>
     */
    public static function all(string $contentRoot): array
    {
        $menus = [];

        foreach (glob(rtrim($contentRoot, '/') . '/navigation/*.yaml') ?: [] as $file) {
            try {
                $menus[] = self::load($contentRoot, basename($file, '.yaml'));
            } catch (ContentException $e) {
                log_message('error', 'Content: navigation {menu}: {message}', ['menu' => basename($file), 'message' => $e->getMessage()]);
            }
        }

        return $menus;
    }
}
