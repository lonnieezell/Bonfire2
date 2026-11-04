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
    private ContentFiles $files;

    public function __construct(string $contentRoot)
    {
        $this->root  = rtrim($contentRoot, '/') . '/';
        $this->files = new ContentFiles($contentRoot);
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

        $raw    = self::serialise(['id' => ContentFiles::uuid(), ...$frontMatter], $body);
        $record = $this->record($collection, $raw, $path);

        return $this->locked($collection, function () use ($collection, $record, $raw, $path): array {
            // The index may still hold a row for a file that was deleted outside the app, and two rows can't share a path.
            if (is_file($this->root . $path) || (new ContentEntryModel())->withDeleted()->where('path', $path)->countAllResults() > 0) {
                throw new ContentException('An entry with this slug already exists.');
            }

            $this->assertUriFree($record);
            $this->files->write($path, $raw);

            return $this->index($collection, $record, $path);
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

        return $this->locked($collection, function () use ($collection, $record, $raw, $path, $expectedHash): array {
            $onDisk = is_file($this->root . $path) ? file_get_contents($this->root . $path) : false;

            if ($onDisk === false || ! hash_equals(hash('sha256', $onDisk), $expectedHash)) {
                throw new SaveConflictException('The entry\'s file changed after you opened it.');
            }

            $this->files->write($path, $raw);

            return $this->index($collection, $record, $path);
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
     * Adds or updates the entry's index row, and its terms, from the file just written.
     *
     * @param array<string, int|string|null> $record
     *
     * @return array<string, int|string|null>
     */
    private function index(Collection $collection, array $record, string $path): array
    {
        $record['mtime'] = (int) filemtime($this->root . $path);

        $model = new ContentEntryModel();
        $db    = $model->db;

        // The row and the pivot rows change together, or not at all.
        $db->transStart();

        if ($model->withDeleted()->find($record['id']) === null) {
            $model->insert($record);
        } else {
            $model->update($record['id'], $record);
        }

        TermIndex::replace([$record['id'] => TermIndex::termIdsOf($collection, $record)]);

        if ($db->transComplete() === false) {
            // Otherwise the failure fails every later transaction on this connection.
            $db->resetTransStatus();

            throw new ContentException('The entry\'s file was written, but the index could not be updated. Run content:reindex to bring it up to date.');
        }

        return $record;
    }

    /**
     * Runs a save with the collection locked.
     *
     * @template T
     *
     * @param callable(): T $save
     *
     * @return T
     */
    private function locked(Collection $collection, callable $save): mixed
    {
        return $this->files->locked($collection->handle, $save);
    }
}
