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
 * Writes a menu's tree file. Like the tree of a structured collection, it's written whole and renamed into
 * place, a save is refused if the file changed since the editor was opened, and comments in it don't survive.
 */
final readonly class NavigationStore
{
    private ContentFiles $files;

    public function __construct(private string $contentRoot)
    {
        $this->files = new ContentFiles($contentRoot);
    }

    /**
     * @throws ContentException      when the menu can't have this tree
     * @throws SaveConflictException when the tree file doesn't match the hash the editor was opened on
     */
    public function save(Navigation $navigation, NavigationTree $tree, string $expectedHash): void
    {
        if ($navigation->maxDepth !== null && $tree->depth() > $navigation->maxDepth) {
            throw new ContentException("The menu is deeper than its max_depth of {$navigation->maxDepth}.");
        }

        $this->files->locked("navigation:{$navigation->handle}", function () use ($navigation, $tree, $expectedHash): void {
            if (! hash_equals(NavigationTree::hash(NavigationTree::raw($this->contentRoot, $navigation->handle)), $expectedHash)) {
                throw new SaveConflictException('The tree file changed after you opened it.');
            }

            $this->assertLinkable($navigation, $tree);

            $this->files->write(NavigationTree::path($navigation->handle), $tree->toYaml());
        });
    }

    /**
     * An entry in the trash can stay in the menu, which skips it until it's restored.
     *
     * @throws ContentException when the tree links to an entry outside the menu's collections
     */
    private function assertLinkable(Navigation $navigation, NavigationTree $tree): void
    {
        $ids = $tree->entryIds();

        if ($ids === []) {
            return;
        }

        $found = $navigation->collections === [] ? [] : (new ContentEntryModel())
            ->withDeleted()
            ->select('id')
            ->whereIn('id', $ids)
            ->whereIn('collection', $navigation->collections)
            ->findAll();

        if (array_diff($ids, array_column($found, 'id')) !== []) {
            throw new ContentException('The menu links to an entry that is not in one of its collections.');
        }
    }
}
