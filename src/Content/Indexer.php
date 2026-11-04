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

use Bonfire\Content\Models\ContentEntryModel;

/**
 * Brings the content_entries index in line with the files under the content root.
 * Every file is read and parsed, so that a changed collection config reaches its
 * entries, but only rows that differ are written. A file that can't be indexed is
 * reported and has no row, the same as after a rebuild.
 */
final readonly class Indexer
{
    private string $root;

    public function __construct(string $contentRoot)
    {
        $this->root = rtrim($contentRoot, '/') . '/';
    }

    /**
     * @param bool $fresh empty the index and rebuild it
     */
    public function reindex(bool $fresh = false): ReindexResult
    {
        // An unmounted content directory must not read as "every entry was deleted".
        if (! is_dir($this->root . 'collections')) {
            return new ReindexResult(failure: "{$this->root} has no collections folder, so the index was left alone.");
        }

        [$records, $errors] = $this->readFiles();

        $entries = new ContentEntryModel();
        $db      = $entries->db;
        $db->transStart();

        if ($fresh) {
            $entries->builder()->emptyTable();
        }

        $rows = array_column($entries->findAll(), null, 'id');

        $stale = array_diff_key($rows, $records);

        foreach (array_keys($stale) as $id) {
            $entries->delete($id, true);
        }

        $added = $updated = $unchanged = 0;

        foreach ($records as $id => $record) {
            if (! isset($rows[$id])) {
                $entries->insert($record);
                $added++;
            } elseif (! $this->differs($rows[$id], $record)) {
                $unchanged++;
            } else {
                $entries->update($id, $record);
                $updated++;
            }
        }

        if ($db->transComplete() === false) {
            // Otherwise the failure fails every later transaction on this connection.
            $db->resetTransStatus();

            return new ReindexResult(errors: $errors, failure: 'The index could not be written, so nothing was changed.');
        }

        return new ReindexResult($added, $updated, count($stale), $unchanged, $errors);
    }

    /**
     * Compares as strings, because the database may return numbers as strings, and leaves
     * out mtime, because a fresh checkout changes it on files that are the same.
     *
     * @param array<string, mixed>           $row
     * @param array<string, int|string|null> $record
     */
    private function differs(array $row, array $record): bool
    {
        return array_diff_assoc(array_diff_key($record, ['mtime' => 0]), $row) !== [];
    }

    /**
     * @return array{array<string, array<string, int|string|null>>, array<string, string>} the index columns of each entry
     *                                                                                     by id, and each unindexable file's error by path
     */
    private function readFiles(): array
    {
        $records = $errors = $uris = [];

        foreach ($this->filesIn('collections', '.yaml') as $configFile) {
            try {
                $collection = Collection::load($this->root, basename($configFile, '.yaml'));
            } catch (ContentException $e) {
                $errors[$configFile] = $e->getMessage();

                continue;
            }

            foreach ($this->filesIn("collections/{$collection->handle}", '.md') as $path) {
                try {
                    $record = $this->readEntry($collection, $path, $records);

                    // A structured collection's URIs come from the tree, which needs all its entries.
                    if (! $collection->structure) {
                        $this->claimUri($uris, $record['uri'], $path);
                    }

                    $records[$record['id']] = $record;
                } catch (ContentException $e) {
                    $errors[$path] = $e->getMessage();
                }
            }

            if ($collection->structure) {
                $this->place($collection, $records, $errors, $uris);
            }
        }

        return [$records, $errors];
    }

    /**
     * Gives a structured collection's entries the parent, position and URI its tree file says. A tree
     * that can't be read is reported and leaves every entry at the root.
     *
     * @param array<string, array<string, int|string|null>> $records
     * @param array<string, string>                         $errors
     * @param array<string, string>                         $uris
     */
    private function place(Collection $collection, array &$records, array &$errors, array &$uris): void
    {
        try {
            $structure = Structure::parse(Structure::raw($this->root, $collection->handle));
        } catch (ContentException $e) {
            $errors[Structure::path($collection->handle)] = $e->getMessage();
            $structure                                    = new Structure([]);
        }

        $own = array_filter($records, static fn (array $record): bool => $record['collection'] === $collection->handle);

        foreach ($structure->place($collection, array_column($own, 'slug', 'id')) as $id => $columns) {
            $record = $columns + $records[$id];

            try {
                Collection::assertUriFits($record['uri']);
                $this->claimUri($uris, $record['uri'], $record['path']);
                $records[$id] = $record;
            } catch (ContentException $e) {
                $errors[$record['path']] = $e->getMessage();
                unset($records[$id]);
            }
        }
    }

    /**
     * @param array<string, array<string, int|string|null>> $records the entries read so far, by id
     *
     * @return array<string, int|string|null> the entry's index columns
     *
     * @throws ContentException when the file can't be indexed
     */
    private function readEntry(Collection $collection, string $path, array $records): array
    {
        if (strlen($path) > 255) {
            throw new ContentException('The path is longer than 255 characters.');
        }

        $record = EntryParser::parse($collection, (string) file_get_contents($this->root . $path), basename($path));

        if (isset($records[$record['id']])) {
            throw new ContentException("Its id is already used by {$records[$record['id']]['path']}.");
        }

        return $record + ['path' => $path, 'mtime' => (int) filemtime($this->root . $path)];
    }

    /**
     * @param array<string, string> $uris the paths of the entries read so far. By lowercase
     *                                    URI, because the index's collation may not tell case apart.
     *
     * @throws ContentException when another entry has this URI
     */
    private function claimUri(array &$uris, ?string $uri, string $path): void
    {
        if ($uri === null) {
            return;
        }

        $key = mb_strtolower($uri);

        if (isset($uris[$key])) {
            throw new ContentException("Its URI {$uri} is already used by {$uris[$key]}.");
        }

        $uris[$key] = $path;
    }

    /**
     * Sorted, because the first file wins when two share an id.
     *
     * @return list<string> the paths from the content root
     */
    private function filesIn(string $folder, string $extension): array
    {
        helper('filesystem');

        $paths = [];

        foreach (directory_map($this->root . $folder, 1) as $name) {
            if (str_ends_with($name, $extension)) {
                $paths[] = "{$folder}/{$name}";
            }
        }

        sort($paths);

        return $paths;
    }
}
