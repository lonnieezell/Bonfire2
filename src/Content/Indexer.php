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
use Bonfire\Content\Models\ContentTermModel;

/**
 * Brings the content_entries index in line with the files under the content root.
 * Every file is read and parsed, so that a changed collection config reaches its
 * entries, but only rows that differ are written. A file that can't be indexed is
 * reported and has no row, the same as after a rebuild. The terms get the same treatment, and every entry's
 * rows in the term pivot are made to match the term slugs in its front matter.
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

        [$terms, $termErrors]             = $this->readTerms();
        [$records, $errors, $collections] = $this->readFiles();
        $errors += $termErrors;

        $entries = new ContentEntryModel();
        $db      = $entries->db;
        $db->transStart();

        if ($fresh) {
            $entries->builder()->emptyTable();
            $db->table('content_terms')->emptyTable();
            $db->table('content_entry_terms')->emptyTable();
        }

        $termIds = $this->syncTerms($terms);
        $rows    = array_column($entries->findAll(), null, 'id');

        $stale = array_diff_key($rows, $records);

        foreach (array_keys($stale) as $id) {
            $entries->delete($id, true);
        }

        TermIndex::forget(array_keys($stale));

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

        // Every entry, changed or not, because a term file may have been added or removed.
        $desired = [];

        foreach ($records as $id => $record) {
            $desired[$id] = TermIndex::termIdsOf($collections[$id], $record, $termIds);
        }

        TermIndex::replace($desired);

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
     * @return array{array<string, array<string, int|string|null>>, array<string, string>, array<string, Collection>} the index columns of each entry
     *                                                                                                                by id, each unindexable file's error by path,
     *                                                                                                                and each entry's collection by id
     */
    private function readFiles(): array
    {
        $records = $errors = $uris = $collections = [];

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
                    $this->claimUri($uris, $record['uri'], $path);
                    $records[$record['id']]     = $record;
                    $collections[$record['id']] = $collection;
                } catch (ContentException $e) {
                    $errors[$path] = $e->getMessage();
                }
            }
        }

        return [$records, $errors, $collections];
    }

    /**
     * Reads the term files of every taxonomy that has a valid config.
     *
     * @return array{array<string, array<string, int|string>>, array<string, string>} the index columns of each term
     *                                                                                by id, and each unindexable file's error by path
     */
    private function readTerms(): array
    {
        $records = $errors = [];

        foreach ($this->filesIn('taxonomies', '.yaml') as $configFile) {
            try {
                $taxonomy = Taxonomy::load($this->root, basename($configFile, '.yaml'));
            } catch (ContentException $e) {
                $errors[$configFile] = $e->getMessage();

                continue;
            }

            foreach ($this->filesIn("taxonomies/{$taxonomy->handle}", '.yaml') as $path) {
                try {
                    $record = $this->readTerm($taxonomy, $path, $records);

                    $records[$record['id']] = $record;
                } catch (ContentException $e) {
                    $errors[$path] = $e->getMessage();
                }
            }
        }

        return [$records, $errors];
    }

    /**
     * @param array<string, array<string, int|string>> $records the terms read so far, by id
     *
     * @return array<string, int|string> the term's index columns
     *
     * @throws ContentException when the file can't be indexed
     */
    private function readTerm(Taxonomy $taxonomy, string $path, array $records): array
    {
        if (strlen($path) > 255) {
            throw new ContentException('The path is longer than 255 characters.');
        }

        $record = TermParser::parse($taxonomy, (string) file_get_contents($this->root . $path), basename($path));

        if (isset($records[$record['id']])) {
            throw new ContentException("Its id is already used by {$records[$record['id']]['path']}.");
        }

        return $record + ['path' => $path, 'mtime' => (int) filemtime($this->root . $path)];
    }

    /**
     * Brings content_terms in line with the term files, writing only the rows that differ.
     *
     * @param array<string, array<string, int|string>> $records the index columns of each term, by id
     *
     * @return array<string, array<string, string>> the id of each term, by slug by taxonomy
     */
    private function syncTerms(array $records): array
    {
        $terms = new ContentTermModel();
        $rows  = array_column($terms->findAll(), null, 'id');

        foreach (array_keys(array_diff_key($rows, $records)) as $id) {
            $terms->delete($id);
        }

        $ids = [];

        foreach ($records as $id => $record) {
            $ids[$record['taxonomy']][$record['slug']] = $id;

            if (! isset($rows[$id])) {
                $terms->insert($record);
            } elseif ($this->differs($rows[$id], $record)) {
                $terms->update($id, $record);
            }
        }

        return $ids;
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

        // A site with no taxonomies has no folder for them.
        if (! is_dir($this->root . $folder)) {
            return $paths;
        }

        foreach (directory_map($this->root . $folder, 1) as $name) {
            if (str_ends_with($name, $extension)) {
                $paths[] = "{$folder}/{$name}";
            }
        }

        sort($paths);

        return $paths;
    }
}
