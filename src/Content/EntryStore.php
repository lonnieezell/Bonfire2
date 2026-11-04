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
use Symfony\Component\Yaml\Yaml;

/**
 * Reads and writes entry files, and keeps the index row of each in step with its file. The file is
 * written whole to a temporary file and renamed into place, so no reader sees half an entry.
 * Comments in the front matter don't survive a save, because the YAML is written afresh.
 */
final readonly class EntryStore
{
    private string $root;

    public function __construct(string $contentRoot)
    {
        $this->root = rtrim($contentRoot, '/') . '/';
    }

    /**
     * An indexed entry as its file holds it now, which is the source of truth, not the index.
     *
     * @return array<string, int|string|null>|null the index columns, with `path` and `mtime`, or null if the index has no such entry
     *
     * @throws ContentException when the file is gone or can't be indexed
     */
    public function read(Collection $collection, string $id): ?array
    {
        $row = (new ContentEntryModel())->where('collection', $collection->handle)->find(strtolower($id));

        if ($row === null) {
            return null;
        }

        $raw = is_file($this->root . $row['path']) ? file_get_contents($this->root . $row['path']) : false;

        if ($raw === false) {
            throw new ContentException('The entry\'s file is gone. Run content:reindex to bring the index up to date.');
        }

        return $this->record($collection, $raw, $row['path']);
    }

    /**
     * Writes a new entry. A dated collection prefixes the file name with the date.
     *
     * @param array<string, mixed> $frontMatter without the id, which is made here
     *
     * @return array<string, int|string|null> the entry's index columns
     *
     * @throws ContentException when the entry can't be indexed, or its file or URI is taken
     */
    public function create(Collection $collection, string $slug, array $frontMatter, string $body, ?string $publishedAt): array
    {
        $prefix   = $collection->dated ? substr($publishedAt ?? date('Y-m-d'), 0, 10) . '.' : '';
        $filename = "{$prefix}{$slug}.md";
        $path     = "collections/{$collection->handle}/{$filename}";

        $raw    = self::serialise(['id' => self::uuid(), ...$frontMatter], $body);
        $record = $this->record($collection, $raw, $path);

        // The tree puts a new entry last at the root.
        if ($collection->structure) {
            $record['uri'] = $collection->uriFor((string) $record['slug']);

            Collection::assertUriFits($record['uri']);
        }

        return $this->locked($collection, function () use ($collection, $record, $raw, $path): array {
            // The index may still hold a row for a file that was deleted outside the app, and two rows can't share a path.
            if (is_file($this->root . $path) || (new ContentEntryModel())->withDeleted()->where('path', $path)->countAllResults() > 0) {
                throw new ContentException('An entry with this slug already exists.');
            }

            // A tree file that can't be read is found before the entry is written, and is never overwritten here.
            $tree = $collection->structure ? Structure::parse(Structure::raw($this->root, $collection->handle)) : null;

            $this->assertUriFree($record);
            $this->write($path, $raw);

            $record = $this->index($record, $path);

            if ($tree !== null) {
                $this->arrangeTree($collection, $tree);
            }

            return $record;
        });
    }

    /**
     * Rewrites an entry's file, if it's still the one the form was opened on.
     *
     * @param array<string, int|string|null> $current      the entry as read() gave it
     * @param array<string, mixed>           $frontMatter  the whole front matter, id included
     * @param string                         $expectedHash the hash of the file the form was opened on
     *
     * @return array<string, int|string|null> the entry's index columns
     *
     * @throws ContentException      when the entry can't be indexed
     * @throws SaveConflictException when the file has changed since the form was opened
     */
    public function update(Collection $collection, array $current, array $frontMatter, string $body, string $expectedHash): array
    {
        $path   = (string) $current['path'];
        $raw    = self::serialise($frontMatter, $body);
        $record = $this->record($collection, $raw, $path);

        // The file doesn't say where the entry sits, so the index row keeps it.
        if ($collection->structure) {
            unset($record['uri'], $record['parent_id'], $record['sort']);
        }

        return $this->locked($collection, function () use ($record, $raw, $path, $expectedHash): array {
            $onDisk = is_file($this->root . $path) ? file_get_contents($this->root . $path) : false;

            if ($onDisk === false || ! hash_equals(hash('sha256', $onDisk), $expectedHash)) {
                throw new SaveConflictException('The entry\'s file changed after you opened it.');
            }

            $this->write($path, $raw);

            return $this->index($record, $path);
        });
    }

    /**
     * Rewrites a structured collection's tree file and gives every entry its new parent, position and URI,
     * if the tree file is still the one the editor was opened on.
     *
     * @param string $expectedHash the hash of the tree file the editor was opened on
     *
     * @throws ContentException      when the tree is deeper than `max_depth` or names an entry the collection doesn't have
     * @throws SaveConflictException when the tree file has changed since the editor was opened
     */
    public function saveTree(Collection $collection, Structure $tree, string $expectedHash): void
    {
        if ($collection->maxDepth !== null && $tree->depth() > $collection->maxDepth) {
            throw new ContentException("The tree is deeper than the collection's max_depth of {$collection->maxDepth}.");
        }

        $this->locked($collection, function () use ($collection, $tree, $expectedHash): void {
            if (! hash_equals(Structure::hash(Structure::raw($this->root, $collection->handle)), $expectedHash)) {
                throw new SaveConflictException('The tree file changed after you opened it.');
            }

            if (array_diff($tree->ids(), array_keys($this->slugs($collection))) !== []) {
                throw new ContentException('The tree names an entry that is not in this collection.');
            }

            $this->arrangeTree($collection, $tree);
        });
    }

    /**
     * The file's contents: the front matter between `---` lines, then the body as it is.
     *
     * @param array<array-key, mixed> $frontMatter
     */
    public static function serialise(array $frontMatter, string $body): string
    {
        return "---\n" . Yaml::dump($frontMatter, 4, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK) . "---\n" . $body;
    }

    /**
     * @return array<string, int|string|null>
     *
     * @throws ContentException when the file wouldn't be indexed
     */
    private function record(Collection $collection, string $raw, string $path): array
    {
        if (strlen($path) > 255) {
            throw new ContentException('The path is longer than 255 characters.');
        }

        return EntryParser::parse($collection, $raw, basename($path)) + ['path' => $path, 'mtime' => (int) @filemtime($this->root . $path)];
    }

    /**
     * Two entries with one URI can't both be indexed.
     *
     * @param array<string, int|string|null> $record
     *
     * @throws ContentException
     */
    private function assertUriFree(array $record): void
    {
        if ($record['uri'] === null) {
            return;
        }

        $taken = (new ContentEntryModel())->withDeleted()->where('uri', $record['uri'])->where('id !=', $record['id'])->countAllResults();

        if ($taken > 0) {
            throw new ContentException('Another entry already has the URI ' . $record['uri'] . '.');
        }
    }

    /**
     * Adds or updates the entry's index row from the file just written.
     *
     * @param array<string, int|string|null> $record
     *
     * @return array<string, int|string|null>
     */
    private function index(array $record, string $path): array
    {
        $record['mtime'] = (int) filemtime($this->root . $path);

        $model = new ContentEntryModel();

        if ($model->withDeleted()->find($record['id']) === null) {
            $model->insert($record);
        } else {
            $model->update($record['id'], $record);
        }

        return $record;
    }

    /**
     * Writes the tree with every entry in it once, then updates the index rows whose place changed.
     */
    private function arrangeTree(Collection $collection, Structure $tree): void
    {
        $model  = new ContentEntryModel();
        $rows   = array_column($model->where('collection', $collection->handle)->orderBy('id')->findAll(), null, 'id');
        $slugs  = array_column($rows, 'slug', 'id');
        $tree   = $tree->arrange($slugs);
        $placed = $tree->place($collection, $slugs);
        $uris   = array_filter(array_column($placed, 'uri'));

        array_map(Collection::assertUriFits(...), $uris);

        // The reindex would drop one of two entries with a URI, so the tree isn't saved with them.
        $lower = array_map(mb_strtolower(...), $uris);
        $taken = $uris === [] ? 0 : (new ContentEntryModel())->withDeleted()->where('collection !=', $collection->handle)->whereIn('uri', $uris)->countAllResults();

        if ($taken > 0 || count($lower) !== count(array_unique($lower))) {
            throw new ContentException('The tree would give two entries the same URI.');
        }

        $this->write(Structure::path($collection->handle), $tree->toYaml());

        $db = $model->db;
        $db->transStart();

        foreach ($placed as $id => $columns) {
            $now = array_replace($columns, array_intersect_key($rows[$id], $columns));

            if (array_map(strval(...), $columns) !== array_map(strval(...), $now)) {
                $model->update($id, $columns);
            }
        }

        if ($db->transComplete() === false) {
            // Otherwise the failure fails every later transaction on this connection.
            $db->resetTransStatus();

            throw new ContentException('The tree file was saved, but the index could not be updated. Run content:reindex.');
        }
    }

    /**
     * @return array<string, string> the slug of each of the collection's indexed entries, by id
     */
    private function slugs(Collection $collection): array
    {
        $rows = (new ContentEntryModel())->select('id, slug')->where('collection', $collection->handle)->orderBy('id')->findAll();

        return array_column($rows, 'slug', 'id');
    }

    private function write(string $path, string $raw): void
    {
        $file = $this->root . $path;
        $dir  = dirname($file);

        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new ContentException('The collection\'s folder could not be created.');
        }

        $temp = tempnam($dir, '.entry-');

        if ($temp === false) {
            throw new ContentException('The entry could not be written.');
        }

        // tempnam() makes a file only its owner can read.
        chmod($temp, is_file($file) ? fileperms($file) & 0o777 : 0o666 & ~umask());

        if (file_put_contents($temp, $raw) !== strlen($raw) || ! rename($temp, $file)) {
            @unlink($temp);

            throw new ContentException('The entry could not be written.');
        }
    }

    /**
     * Runs a save with the collection locked, so that two saves can't both pass their checks and one overwrite the other,
     * or two new entries take the same URI. One lock for the collection, so locks don't pile up as entries do.
     *
     * @template T
     *
     * @param callable(): T $save
     *
     * @return T
     */
    private function locked(Collection $collection, callable $save): mixed
    {
        $lock = WRITEPATH . 'cache/content-' . hash('sha256', $this->root . $collection->handle) . '.lock';
        $file = @fopen($lock, 'cb');

        if ($file === false || ! flock($file, LOCK_EX)) {
            throw new ContentException('The entry could not be locked for writing.');
        }

        try {
            return $save();
        } finally {
            flock($file, LOCK_UN);
            fclose($file);
        }
    }

    private static function uuid(): string
    {
        $bytes    = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
