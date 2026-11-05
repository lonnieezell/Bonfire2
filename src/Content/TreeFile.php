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

use Symfony\Component\Yaml\Yaml;

/**
 * What the trees under trees/ have in common: a file that an editor saves whole, of nodes with `children`.
 */
trait TreeFile
{
    /**
     * Where the tree file is, from the content root.
     */
    abstract public static function path(string $handle): string;

    /**
     * The tree file as it is now, or an empty string if there is none.
     */
    public static function raw(string $contentRoot, string $handle): string
    {
        $file = rtrim($contentRoot, '/') . '/' . self::path($handle);

        return is_file($file) ? (string) file_get_contents($file) : '';
    }

    /**
     * What the editor posts back: a hash of the file it was opened on. A missing file hashes as an empty one.
     */
    public static function hash(string $raw): string
    {
        return hash('sha256', $raw);
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
     * @param list<array<string, mixed>> $nodes
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
     * The nodes as the file holds them, without empty `children`.
     *
     * @param list<array<string, mixed>> $nodes
     *
     * @return list<array<string, mixed>>
     */
    private static function forFile(array $nodes): array
    {
        return array_map(static function (array $node): array {
            $children = $node['children'];
            unset($node['children']);

            return $children === [] ? $node : [...$node, 'children' => self::forFile($children)];
        }, $nodes);
    }
}
