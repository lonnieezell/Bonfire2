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
 * Writes a global set's file. Like an entry, it's written whole to a temporary file and renamed into place, a save is
 * refused if the file changed since the form was opened, and comments in it don't survive, because the YAML is written afresh.
 */
final readonly class GlobalStore
{
    private ContentFiles $files;

    public function __construct(private string $contentRoot)
    {
        $this->files = new ContentFiles($contentRoot);
    }

    /**
     * @param array<array-key, mixed> $data         the whole file's values
     * @param string                  $expectedHash the hash of the file the form was opened on
     *
     * @throws ContentException      when the file can't be written
     * @throws SaveConflictException when the file has changed since the form was opened
     */
    public function save(GlobalSet $set, array $data, string $expectedHash): void
    {
        $path = "globals/{$set->handle}.yaml";
        $file = rtrim($this->contentRoot, '/') . "/{$path}";

        $this->files->locked("globals:{$set->handle}", function () use ($path, $file, $data, $expectedHash): void {
            $onDisk = is_file($file) ? file_get_contents($file) : false;

            if ($onDisk === false || ! hash_equals(hash('sha256', $onDisk), $expectedHash)) {
                throw new SaveConflictException('The globals file changed after you opened it.');
            }

            // An empty mapping would dump as `{  }`, and an empty file is the same set.
            $this->files->write($path, $data === [] ? '' : Yaml::dump($data, 4, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));
        });
    }
}
