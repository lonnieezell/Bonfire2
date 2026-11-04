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

/**
 * What the term store needs to write files under the content root: whole-file writes, a lock per
 * taxonomy, and ids.
 */
final readonly class ContentFiles
{
    private string $root;

    public function __construct(string $contentRoot)
    {
        $this->root = rtrim($contentRoot, '/') . '/';
    }

    /**
     * @phpstan-assert-if-true string $id
     */
    public static function isUuid(mixed $id): bool
    {
        return is_string($id) && preg_match('/^[0-9a-f]{8}(-[0-9a-f]{4}){3}-[0-9a-f]{12}$/i', $id) === 1;
    }

    public static function uuid(): string
    {
        $bytes    = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    /**
     * Writes the file whole to a temporary file and renames it into place, so no reader sees half of it.
     *
     * @param string $path from the content root
     *
     * @throws ContentException
     */
    public function write(string $path, string $raw): void
    {
        $file = $this->root . $path;
        $dir  = dirname($file);

        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new ContentException('The folder could not be created.');
        }

        $temp = tempnam($dir, '.entry-');

        if ($temp === false) {
            throw new ContentException('The file could not be written.');
        }

        // tempnam() makes a file only its owner can read.
        chmod($temp, is_file($file) ? fileperms($file) & 0o777 : 0o666 & ~umask());

        if (file_put_contents($temp, $raw) !== strlen($raw) || ! rename($temp, $file)) {
            @unlink($temp);

            throw new ContentException('The file could not be written.');
        }
    }

    /**
     * Runs a save with the group of files locked, so that two saves can't both pass their checks and one overwrite the other,
     * or two new files take the same URI. One lock for the group, so locks don't pile up as files do.
     *
     * @template T
     *
     * @param string        $group e.g. a collection's handle
     * @param callable(): T $save
     *
     * @return T
     *
     * @throws ContentException
     */
    public function locked(string $group, callable $save): mixed
    {
        $lock = WRITEPATH . 'cache/content-' . hash('sha256', $this->root . $group) . '.lock';
        $file = @fopen($lock, 'cb');

        if ($file === false || ! flock($file, LOCK_EX)) {
            throw new ContentException('The file could not be locked for writing.');
        }

        try {
            return $save();
        } finally {
            flock($file, LOCK_UN);
            fclose($file);
        }
    }
}
