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
 *
 * Deleting moves a file to `.trash/` and soft-deletes its row, which keeps the original path. A trashed entry
 * keeps holding its slug and URI, and its id stays in the tree file. A live page's ancestors are always live.
 */
final readonly class EntryStore
{
    public const TRASH_HOLDS_SLUG = 'A page in the trash uses this slug. Restore or purge it first.';

    private string $root;
    private ContentFiles $files;
    private Redirects $redirects;

    public function __construct(string $contentRoot)
    {
        $this->root      = rtrim($contentRoot, '/') . '/';
        $this->files     = new ContentFiles($contentRoot);
        $this->redirects = new Redirects($contentRoot);
    }

    /**
     * An indexed entry as its file holds it now, which is the source of truth, not the index.
     *
     * @param bool $orTrashed give a trashed entry as its row, for a form that was opened before the delete
     *
     * @return array<string, int|string|null>|null the index columns, with `path` and `mtime`, or null if the index has no such entry
     *
     * @throws ContentException when the file is gone or can't be indexed
     */
    public function read(Collection $collection, string $id, bool $orTrashed = false): ?array
    {
        $row = (new ContentEntryModel())->withDeleted($orTrashed)->where('collection', $collection->handle)->find(strtolower($id));

        if ($row === null) {
            return null;
        }

        if ($row['deleted_at'] !== null) {
            return $row;
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

        // The tree puts a new entry last at the root.
        if ($collection->structure) {
            $record['uri'] = $collection->uriFor((string) $record['slug']);

            Collection::assertUriFits($record['uri']);
        }

        return $this->files->locked($collection->handle, function () use ($collection, $record, $raw, $path): array {
            // The index may still hold a row for a file that was deleted outside the app, and two rows can't share a path.
            $holder = (new ContentEntryModel())->withDeleted()->select('deleted_at')->where('path', $path)->first();

            if (! is_file($this->root . $path) && ($holder['deleted_at'] ?? null) !== null) {
                throw new ContentException(self::TRASH_HOLDS_SLUG);
            }

            if (is_file($this->root . $path) || $holder !== null) {
                throw new ContentException('An entry with this slug already exists.');
            }

            // A tree file that can't be read is found before the entry is written, and is never overwritten here.
            $tree = $this->treeOf($collection);

            $this->assertUriFree($record);
            $this->files->write($path, $raw);

            $record = $this->index($collection, $record, $path);

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

        return $this->files->locked($collection->handle, function () use ($collection, $record, $raw, $path, $expectedHash): array {
            $onDisk = is_file($this->root . $path) ? file_get_contents($this->root . $path) : false;

            if ($onDisk === false || ! hash_equals(hash('sha256', $onDisk), $expectedHash)) {
                throw new SaveConflictException('The entry\'s file changed after you opened it.');
            }

            $this->files->write($path, $raw);

            return $this->index($collection, $record, $path);
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

        $this->files->locked($collection->handle, function () use ($collection, $tree, $expectedHash): void {
            if (! hash_equals(Structure::hash(Structure::raw($this->root, $collection->handle)), $expectedHash)) {
                throw new SaveConflictException('The tree file changed after you opened it.');
            }

            $rows = $this->rowsOf($collection);

            if (array_diff($tree->ids(), array_keys($rows)) !== []) {
                throw new ContentException('The tree names an entry that is not in this collection.');
            }

            $trashed = array_keys(array_diff_key($rows, self::liveSlugs($rows)));

            foreach ($trashed as $id) {
                if (array_diff($tree->below($id), $trashed) !== []) {
                    throw new ContentException('A live page can\'t go under a page in the trash.');
                }
            }

            $this->arrangeTree($collection, $tree);
        });
    }

    /**
     * How many live pages are below each of these entries. A tree file that can't be read says none,
     * and trash() refuses the delete with the reason.
     *
     * @param list<string> $ids
     *
     * @return array<string, int> by id
     */
    public function descendantCounts(Collection $collection, array $ids): array
    {
        $counts = array_fill_keys($ids, 0);

        if (! $collection->structure) {
            return $counts;
        }

        try {
            $tree = Structure::parse(Structure::raw($this->root, $collection->handle))->arrange(self::liveSlugs($this->rowsOf($collection)));
        } catch (ContentException) {
            return $counts;
        }

        foreach ($ids as $id) {
            $counts[$id] = count($tree->below($id));
        }

        return $counts;
    }

    /**
     * Moves a live entry and the pages below it to the trash. The file goes as it is on disk. The tree file isn't
     * rewritten, so that a restore returns each page to its exact spot.
     *
     * @param int $descendants how many pages below it the user was told would go with it
     *
     * @return bool false when the collection has no such live entry
     *
     * @throws ContentException when it's refused, or the index couldn't be updated
     */
    public function trash(Collection $collection, string $id, int $descendants): bool
    {
        $id = strtolower($id);

        return $this->files->locked($collection->handle, function () use ($collection, $id, $descendants): bool {
            $rows = $this->rowsOf($collection);
            $live = self::liveSlugs($rows);

            if (! isset($live[$id])) {
                return false;
            }

            $tree  = $this->treeOf($collection);
            $below = $tree?->arrange($live)->below($id) ?? [];

            if (count($below) !== $descendants) {
                throw new ContentException('The pages below this one have changed. Look again, then delete it again.');
            }

            $moves = [];

            foreach ([$id, ...$below] as $each) {
                $path = (string) $rows[$each]['path'];
                $bin  = ContentFiles::trashPath($path);

                if (! is_file($this->root . $path)) {
                    throw new ContentException("The file of {$rows[$each]['title']} is gone. Run content:reindex to bring the index up to date.");
                }

                if (is_file($this->root . $bin)) {
                    throw new ContentException("{$bin} already exists, so nothing was deleted. Move that file away first.");
                }

                $moves[$path] = $bin;
            }

            $this->files->move($moves);

            // After a --fresh reindex, this is the date the entry went to the trash.
            foreach ($moves as $bin) {
                @touch($this->root . $bin);
            }

            $this->inTransaction(function () use ($collection, $tree, $id, $below): void {
                (new ContentEntryModel())->delete([$id, ...$below]);

                if ($tree !== null) {
                    $this->updatePlaces($this->placement($collection, $tree));
                }
            }, 'The files went to the trash, but the index could not be updated. Run content:reindex to bring it up to date.');

            return true;
        });
    }

    /**
     * Brings an entry back from the trash, with every trashed page the tree file places below it. All of them or none.
     *
     * @return bool false when the index has no such trashed entry
     *
     * @throws ContentException when it's refused, or the index couldn't be updated
     */
    public function restore(string $id): bool
    {
        $id  = strtolower($id);
        $row = (new ContentEntryModel())->onlyDeleted()->find($id);

        if ($row === null) {
            return false;
        }

        $collection = Collection::load($this->root, $row['collection']);

        return $this->files->locked($collection->handle, function () use ($collection, $id): bool {
            $rows = $this->rowsOf($collection);

            if (($rows[$id]['deleted_at'] ?? null) === null) {
                return false;
            }

            $tree = $this->treeOf($collection);

            [$group, $moved] = $tree === null ? [[$id => $rows[$id]['uri']], []] : $this->groupToRestore($collection, $tree, $rows, $id);

            $this->assertRestorable($rows, $group, $moved);

            $moves = [];

            foreach (array_keys($group) as $each) {
                $moves[ContentFiles::trashPath((string) $rows[$each]['path'])] = (string) $rows[$each]['path'];
            }

            $this->files->move($moves);

            $placement = null;

            $this->inTransaction(function () use ($collection, $tree, $group, &$placement): void {
                (new ContentEntryModel())->update(array_keys($group), ['deleted_at' => null]);

                if ($tree !== null) {
                    $this->updatePlaces($placement = $this->placement($collection, $tree));
                }
            }, 'The files came back from the trash, but the index could not be updated. Run content:reindex to bring it up to date.');

            // Live pages below it that a hand-moved file put there get new URIs.
            if ($placement !== null) {
                $this->remember($this->movedUris($placement));
            }

            return true;
        });
    }

    /**
     * Removes one trashed entry for good: its file, its row, its term pivot rows and its id in the tree file.
     * Pages below it stay in the trash, and move up to its spot.
     *
     * @return bool false when the index has no such trashed entry
     *
     * @throws ContentException when it's refused, or the index couldn't be updated
     */
    public function purge(string $id): bool
    {
        $id  = strtolower($id);
        $row = (new ContentEntryModel())->onlyDeleted()->find($id);

        if ($row === null) {
            return false;
        }

        try {
            $collection = Collection::load($this->root, $row['collection']);
        } catch (ContentException) {
            // With its config gone the collection has no tree to update.
            $collection = null;
        }

        return $this->files->locked($row['collection'], function () use ($collection, $id, $row): bool {
            if ((new ContentEntryModel())->onlyDeleted()->find($id) === null) {
                return false;
            }

            $tree = $this->treeOf($collection);
            $file = $this->root . ContentFiles::trashPath($row['path']);

            if (is_file($file) && ! @unlink($file)) {
                throw new ContentException('The file in the trash could not be deleted.');
            }

            $this->inTransaction(static function () use ($id): void {
                (new ContentEntryModel())->delete($id, true);
                TermIndex::forget([$id]);
            }, 'The file was deleted, but the index could not be updated. Run content:reindex to bring it up to date.');

            if ($tree !== null) {
                $this->saveArrangement($collection, $this->placement($collection, $tree));
            }

            return true;
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

        $holders = (new ContentEntryModel())->withDeleted()->select('deleted_at')->where('uri', $record['uri'])->where('id !=', $record['id'])->findAll();

        if ($holders !== []) {
            throw new ContentException(in_array(null, array_column($holders, 'deleted_at'), true) ? 'Another entry already has the URI ' . $record['uri'] . '.' : self::TRASH_HOLDS_SLUG);
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
        $existing        = (new ContentEntryModel())->withDeleted()->find($record['id']);
        $old             = $existing['uri'] ?? null;

        // The row and the pivot rows change together, or not at all.
        $this->inTransaction(static function () use ($collection, $record, $existing): void {
            $model = new ContentEntryModel();

            if ($existing === null) {
                $model->insert($record);
            } else {
                $model->update($record['id'], $record);
            }

            TermIndex::replace([$record['id'] => TermIndex::termIdsOf($collection, $record)]);
        }, 'The entry\'s file was written, but the index could not be updated. Run content:reindex to bring it up to date.');

        if (array_key_exists('uri', $record) && $record['uri'] !== $old) {
            $this->remember([$record['id'] => [$old, $record['uri']]]);
        }

        return $record;
    }

    /**
     * The entries that a restore of this one brings back, and any live pages below it, which only a
     * file moved by hand can put there, and whose URIs change with it.
     *
     * @param array<string, array<string, mixed>> $rows
     *
     * @return array{array<string, string|null>, array<string, string|null>} the URI each entry will have, and each of those live pages, by id
     *
     * @throws ContentException when its parent is still in the trash
     */
    private function groupToRestore(Collection $collection, Structure $tree, array $rows, string $id): array
    {
        $slugs  = array_column($rows, 'slug', 'id');
        $tree   = $tree->arrange($slugs);
        $parent = $tree->parentOf($id);

        if ($parent !== null && $rows[$parent]['deleted_at'] !== null) {
            throw new ContentException("{$rows[$parent]['title']} is above this page and is in the trash. Restore it first.");
        }

        $live   = self::liveSlugs($rows);
        $below  = $tree->below($id);
        $ids    = array_flip([$id, ...array_diff($below, array_keys($live))]);
        $placed = $tree->place($collection, $live + array_intersect_key($slugs, $ids));
        $uris   = array_map(static fn (array $columns): ?string => $columns['uri'], $placed);
        $moved  = array_filter(array_intersect_key($uris, array_flip($below), $live), static fn (?string $uri, string $each): bool => $uri !== $rows[$each]['uri'], ARRAY_FILTER_USE_BOTH);

        return [array_intersect_key($uris, $ids), $moved];
    }

    /**
     * @param array<string, array<string, mixed>> $rows  the collection's rows
     * @param array<string, string|null>          $group the URI each entry will have, by id
     * @param array<string, string|null>          $moved the new URI of each live page below them, by id
     *
     * @throws ContentException naming what stands in the way
     */
    private function assertRestorable(array $rows, array $group, array $moved): void
    {
        foreach (array_keys($group) as $id) {
            $path = $rows[$id]['path'];

            if (! is_file($this->root . ContentFiles::trashPath($path))) {
                throw new ContentException("The file of {$rows[$id]['title']} is missing from the trash, so nothing was restored.");
            }

            $holder = (new ContentEntryModel())->select('title')->where('path', $path)->first();

            if (is_file($this->root . $path) || $holder !== null) {
                throw new ContentException("{$path} is taken" . ($holder === null ? '' : " by {$holder['title']}") . ', so nothing was restored.');
            }
        }

        $uris = array_filter($group + $moved);

        array_map(Collection::assertUriFits(...), $uris);

        $lower = array_map(mb_strtolower(...), $uris);

        if (count($lower) !== count(array_unique($lower))) {
            throw new ContentException('Two of these pages would have the same URI, so nothing was restored.');
        }

        $holder = $uris === [] ? null : (new ContentEntryModel())->select('title, uri')->whereIn('uri', array_values($uris))->first();

        if ($holder !== null) {
            throw new ContentException("{$holder['title']} has the URI {$holder['uri']}, so nothing was restored.");
        }
    }

    /**
     * Writes the tree with every entry in it once, trashed ones too, then updates the index rows whose place changed.
     */
    private function arrangeTree(Collection $collection, Structure $tree): void
    {
        $placement = $this->placement($collection, $tree);

        $this->assertPlacesFree($collection, $placement);
        $this->saveArrangement($collection, $placement);
    }

    /**
     * Where each entry goes in this tree. A live entry is placed among the live ones, and a trashed
     * one where it would come back, so that it keeps holding that URI.
     *
     * @return array{tree: Structure, placed: array<string, array{parent_id: string|null, sort: int, uri: string|null}>, rows: array<string, array<string, mixed>>}
     */
    private function placement(Collection $collection, Structure $tree): array
    {
        $rows  = $this->rowsOf($collection);
        $slugs = array_column($rows, 'slug', 'id');
        $live  = self::liveSlugs($rows);

        return [
            'tree'   => $tree->arrange($slugs),
            'placed' => $tree->place($collection, $live) + array_diff_key($tree->place($collection, $slugs), $live),
            'rows'   => $rows,
        ];
    }

    /**
     * The reindex would drop one of two live entries with a URI, so the tree isn't saved with them. A page in the
     * trash holds its URI against a page that moves there, but not against one that is there already.
     *
     * @param array{tree: Structure, placed: array<string, array{parent_id: string|null, sort: int, uri: string|null}>, rows: array<string, array<string, mixed>>} $placement
     *
     * @throws ContentException
     */
    private function assertPlacesFree(Collection $collection, array $placement): void
    {
        ['placed' => $placed, 'rows' => $rows] = $placement;

        array_map(Collection::assertUriFits(...), array_filter(array_column($placed, 'uri')));

        $live  = array_intersect_key($placed, self::liveSlugs($rows));
        $uris  = array_filter(array_column($live, 'uri'));
        $lower = array_map(mb_strtolower(...), $uris);

        if (count($lower) !== count(array_unique($lower))) {
            throw new ContentException('The tree would give two entries the same URI.');
        }

        $moved = [];

        foreach ($live as $id => $columns) {
            if ($columns['uri'] !== null && $columns['uri'] !== $rows[$id]['uri']) {
                $moved[] = mb_strtolower($columns['uri']);
            }
        }

        $holding = array_map(mb_strtolower(...), array_filter(array_column(array_diff_key($placed, $live), 'uri')));
        $others  = $uris === [] ? [] : (new ContentEntryModel())->withDeleted()->select('uri, deleted_at')->where('collection !=', $collection->handle)->whereIn('uri', $uris)->findAll();

        foreach ($others as $other) {
            if ($other['deleted_at'] === null) {
                throw new ContentException('The tree would give two entries the same URI.');
            }

            $holding[] = mb_strtolower($other['uri']);
        }

        if (array_intersect($moved, $holding) !== []) {
            throw new ContentException(self::TRASH_HOLDS_SLUG);
        }
    }

    /**
     * @param array{tree: Structure, placed: array<string, array{parent_id: string|null, sort: int, uri: string|null}>, rows: array<string, array<string, mixed>>} $placement
     *
     * @throws ContentException
     */
    private function saveArrangement(Collection $collection, array $placement): void
    {
        $this->files->write(Structure::path($collection->handle), $placement['tree']->toYaml());

        $this->inTransaction(fn () => $this->updatePlaces($placement), 'The tree file was saved, but the index could not be updated. Run content:reindex.');
        $this->remember($this->movedUris($placement));
    }

    /**
     * The live entries whose URI this placement changes, with the old and the new one, by id.
     *
     * @param array{tree: Structure, placed: array<string, array{parent_id: string|null, sort: int, uri: string|null}>, rows: array<string, array<string, mixed>>} $placement
     *
     * @return array<string, array{string|null, string|null}>
     */
    private function movedUris(array $placement): array
    {
        $moves = [];

        foreach ($placement['placed'] as $id => $columns) {
            $row = $placement['rows'][$id];

            if ($row['deleted_at'] === null && $columns['uri'] !== $row['uri']) {
                $moves[$id] = [$row['uri'], $columns['uri']];
            }
        }

        return $moves;
    }

    /**
     * Records the old URIs for the redirects. This is after the change, which stays if it fails. Moves with no old
     * URI only drop redirects, so a file that can't be used is logged and doesn't fail a new entry.
     *
     * @param array<string, array{string|null, string|null}> $moves the old and new URI of each entry, by id
     *
     * @throws ContentException
     */
    private function remember(array $moves): void
    {
        try {
            $this->redirects->record($moves);
        } catch (ContentException $e) {
            if (array_filter(array_column($moves, 0)) === []) {
                log_message('error', 'Content: ' . Redirects::FILE . ': {message}', ['message' => $e->getMessage()]);

                return;
            }

            throw new ContentException('The change was saved, but its old URIs could not be kept for redirects. ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @param array{tree: Structure, placed: array<string, array{parent_id: string|null, sort: int, uri: string|null}>, rows: array<string, array<string, mixed>>} $placement
     */
    private function updatePlaces(array $placement): void
    {
        $model = new ContentEntryModel();

        foreach ($placement['placed'] as $id => $columns) {
            $now = array_replace($columns, array_intersect_key($placement['rows'][$id], $columns));

            if (array_map(strval(...), $columns) !== array_map(strval(...), $now)) {
                $model->update($id, $columns);
            }
        }
    }

    /**
     * @param string $failure what to say if the change can't be written
     *
     * @throws ContentException
     */
    private function inTransaction(callable $change, string $failure): void
    {
        $db = (new ContentEntryModel())->db;
        $db->transStart();

        $change();

        if ($db->transComplete() === false) {
            // Otherwise the failure fails every later transaction on this connection.
            $db->resetTransStatus();

            throw new ContentException($failure);
        }
    }

    /**
     * The tree of a structured collection, and null for any other. With no collection there's no tree to update.
     *
     * @throws ContentException when the tree file can't be read
     */
    private function treeOf(?Collection $collection): ?Structure
    {
        return $collection?->structure ? Structure::parse(Structure::raw($this->root, $collection->handle)) : null;
    }

    /**
     * @return array<string, array<string, mixed>> the collection's rows without their contents, trashed ones too, by id
     */
    private function rowsOf(Collection $collection): array
    {
        return array_column((new ContentEntryModel())->withDeleted()->select('id, slug, uri, parent_id, sort, title, path, deleted_at')->where('collection', $collection->handle)->orderBy('id')->findAll(), null, 'id');
    }

    /**
     * @param array<string, array<string, mixed>> $rows
     *
     * @return array<string, string> the slug of each live entry, by id
     */
    private static function liveSlugs(array $rows): array
    {
        return array_column(array_filter($rows, static fn (array $row): bool => $row['deleted_at'] === null), 'slug', 'id');
    }
}
