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
 * Writes a navigation's tree file. The file is written whole to a temporary file and renamed into place.
 * Comments in the tree file don't survive a save, because the YAML is written afresh.
 */
final readonly class NavigationStore
{
    private string $root;
    private ContentFiles $files;

    public function __construct(string $contentRoot)
    {
        $this->root  = rtrim($contentRoot, '/') . '/';
        $this->files = new ContentFiles($contentRoot);
    }

    /**
     * Rewrites the tree file, if it's still the one the editor was opened on.
     *
     * @param string $expectedHash the hash of the tree file the editor was opened on
     *
     * @throws ContentException      when the tree is deeper than `max_depth`, or links an entry that doesn't exist or isn't in an allowed collection
     * @throws SaveConflictException when the tree file has changed since the editor was opened
     */
    public function save(Navigation $navigation, NavigationTree $tree, string $expectedHash): void
    {
        if ($navigation->maxDepth !== null && $tree->depth() > $navigation->maxDepth) {
            throw new ContentException("The menu is deeper than the navigation's max_depth of {$navigation->maxDepth}.");
        }

        $this->files->locked("navigation/{$navigation->handle}", function () use ($navigation, $tree, $expectedHash): void {
            if (! hash_equals(NavigationTree::hash($navigation->rawTree($this->root)), $expectedHash)) {
                throw new SaveConflictException('The tree file changed after you opened it.');
            }

            $this->assertEntriesAllowed($navigation, $tree->entryIds());
            $this->files->write($navigation->treePath(), $tree->toYaml());
        });
    }

    /**
     * @param list<string> $ids
     *
     * @throws ContentException
     */
    private function assertEntriesAllowed(Navigation $navigation, array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $model = (new ContentEntryModel())->select('id')->whereIn('id', $ids);

        if ($navigation->collections !== []) {
            $model->whereIn('collection', $navigation->collections);
        }

        // The model leaves out entries in the trash.
        if (count($model->findAll()) !== count($ids)) {
            throw new ContentException('The menu links an entry that does not exist, or is not in a collection this navigation allows.');
        }
    }
}
