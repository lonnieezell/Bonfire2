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
 * What the stores need to write files under the content root: whole-file writes, moves, a lock per
 * collection or taxonomy, and ids.
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

    /**
     * Where an entry's file goes in the trash: `collections/<handle>/<file>` to `.trash/<handle>/<file>`.
     */
    public static function trashPath(string $path): string
    {
        return '.trash/' . substr($path, strlen('collections/'));
    }

    /**
     * Where a file in the trash came from, which is trashPath() the other way.
     */
    public static function livePath(string $trashPath): string
    {
        return 'collections/' . substr($trashPath, strlen('.trash/'));
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

        $this->makeFolder($dir);

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
     * Moves files, all of them or none: a move that fails puts back those already moved.
     *
     * @param array<string, string> $moves where each file goes by where it is, from the content root
     *
     * @throws ContentException
     */
    public function move(array $moves): void
    {
        $moved = [];

        try {
            foreach ($moves as $from => $to) {
                $this->makeFolder(dirname($this->root . $to));

                if (! @rename($this->root . $from, $this->root . $to)) {
                    throw new ContentException("{$from} could not be moved.");
                }

                $moved[$to] = $from;
            }
        } catch (ContentException $e) {
            foreach ($moved as $to => $from) {
                @rename($this->root . $to, $this->root . $from);
            }

            throw $e;
        }
    }

    /**
     * Runs a save with the group of files locked, so that two saves can't both pass their checks and one overwrite the other,
     * or two new files take the same URI. One lock for the group, so locks don't pile up as files do.
     * Every save, trash, restore, purge and tree move comes through here, so the cached pages are cleared
     * after each, even a refused one, since a change that failed halfway may still have changed files.
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
            PageCache::clear();
        }
    }

    /**
     * @throws ContentException
     */
    private function makeFolder(string $dir): void
    {
        if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new ContentException('The folder could not be created.');
        }
    }
}
