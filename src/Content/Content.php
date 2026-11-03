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
 * The content query API, available as content().
 */
final readonly class Content
{
    public function __construct(private string $contentRoot)
    {
    }

    /**
     * Starts a query on a collection's entries.
     *
     * @throws ContentException when the collection doesn't exist or its config is invalid
     */
    public function collection(string $handle): EntryQuery
    {
        return new EntryQuery(Collection::load($this->contentRoot, $handle));
    }

    /**
     * Finds an entry by its UUID, in any collection and whether or not it's published.
     */
    public function find(string $id): ?Entry
    {
        $row = (new ContentEntryModel())->find(strtolower($id));

        return $row === null ? null : Entry::fromRow($row);
    }
}
