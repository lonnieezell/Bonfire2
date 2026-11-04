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

use Bonfire\Content\Models\ContentTermModel;
use Symfony\Component\Yaml\Yaml;

/**
 * Reads and writes term files, and keeps the index row of each in step with its file. Like an entry's file,
 * a term's is written whole and renamed into place. Comments in it don't survive a save. A save doesn't check
 * whether the file changed since the form was opened, so the last one wins.
 */
final readonly class TermStore
{
    private string $root;
    private ContentFiles $files;

    public function __construct(string $contentRoot)
    {
        $this->root  = rtrim($contentRoot, '/') . '/';
        $this->files = new ContentFiles($contentRoot);
    }

    /**
     * An indexed term as its file holds it now, which is the source of truth, not the index.
     *
     * @return array<string, int|string>|null the index columns, with `path` and `mtime`, or null if the index has no such term
     *
     * @throws ContentException when the file is gone or can't be indexed
     */
    public function read(Taxonomy $taxonomy, string $slug): ?array
    {
        $row = (new ContentTermModel())->lookup($taxonomy->handle, $slug);

        if ($row === null) {
            return null;
        }

        $raw = is_file($this->root . $row['path']) ? file_get_contents($this->root . $row['path']) : false;

        if ($raw === false) {
            throw new ContentException('The term\'s file is gone. Run content:reindex to bring the index up to date.');
        }

        return $this->record($taxonomy, $raw, $row['path']);
    }

    /**
     * Writes a new term.
     *
     * @param array<string, mixed> $data without the id, which is made here
     *
     * @return array<string, int|string> the term's index columns
     *
     * @throws ContentException when the term can't be indexed, or its file is taken
     */
    public function create(Taxonomy $taxonomy, string $slug, array $data): array
    {
        // The slug is a file name.
        if (preg_match(Taxonomy::SLUG, $slug) !== 1) {
            throw new ContentException('A term\'s slug is letters, numbers, - and _.');
        }

        $path   = "taxonomies/{$taxonomy->handle}/{$slug}.yaml";
        $raw    = self::serialise(['id' => ContentFiles::uuid(), ...$data]);
        $record = $this->record($taxonomy, $raw, $path);

        return $this->files->locked("taxonomy:{$taxonomy->handle}", function () use ($record, $raw, $path): array {
            // The index may still hold a row for a file that was deleted outside the app, and two rows can't share a path.
            if (is_file($this->root . $path) || (new ContentTermModel())->where('path', $path)->countAllResults() > 0) {
                throw new ContentException('A term with this slug already exists.');
            }

            $this->files->write($path, $raw);

            return $this->index($record, $path);
        });
    }

    /**
     * Rewrites a term's file.
     *
     * @param array<string, int|string> $current as read() gave it
     * @param array<string, mixed>      $data    the whole file's keys, id included
     *
     * @return array<string, int|string> the term's index columns
     *
     * @throws ContentException when the term can't be indexed
     */
    public function update(Taxonomy $taxonomy, array $current, array $data): array
    {
        $path   = (string) $current['path'];
        $raw    = self::serialise($data);
        $record = $this->record($taxonomy, $raw, $path);

        return $this->files->locked("taxonomy:{$taxonomy->handle}", function () use ($record, $raw, $path): array {
            $this->files->write($path, $raw);

            return $this->index($record, $path);
        });
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function serialise(array $data): string
    {
        return Yaml::dump($data, 4, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
    }

    /**
     * @return array<string, int|string>
     *
     * @throws ContentException when the file wouldn't be indexed
     */
    private function record(Taxonomy $taxonomy, string $raw, string $path): array
    {
        if (strlen($path) > 255) {
            throw new ContentException('The path is longer than 255 characters.');
        }

        return TermParser::parse($taxonomy, $raw, basename($path)) + ['path' => $path, 'mtime' => (int) @filemtime($this->root . $path)];
    }

    /**
     * Adds or updates the term's index row from the file just written.
     *
     * @param array<string, int|string> $record
     *
     * @return array<string, int|string>
     */
    private function index(array $record, string $path): array
    {
        $record['mtime'] = (int) filemtime($this->root . $path);

        $model = new ContentTermModel();

        if ($model->find($record['id']) === null) {
            $model->insert($record);
        } else {
            $model->update($record['id'], $record);
        }

        return $record;
    }
}
