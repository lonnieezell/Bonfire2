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
 * An indexed term: its columns and the keys of its file.
 */
final readonly class Term
{
    /**
     * @param array<array-key, mixed> $data  the keys of the term's file, which are its blueprint's fields
     * @param int                     $count how many published entries have the term
     */
    public function __construct(
        public string $id,
        public string $taxonomy,
        public string $slug,
        public string $title,
        public array $data,
        public int $count,
    ) {
    }

    /**
     * @param array<string, mixed> $row a content_terms row
     */
    public static function fromRow(array $row, int $count): self
    {
        return new self(
            $row['id'],
            $row['taxonomy'],
            $row['slug'],
            $row['title'],
            json_decode($row['data'], true, flags: JSON_THROW_ON_ERROR),
            $count,
        );
    }

    /**
     * A key of the term's file.
     */
    public function field(string $name, mixed $default = null): mixed
    {
        return $this->data[$name] ?? $default;
    }

    /**
     * The published entries that have this term, in any collection, the most recent first.
     *
     * @return list<Entry>
     */
    public function entries(): array
    {
        $rows = (new ContentEntryModel())
            ->published()
            ->whereIn('id', TermIndex::entryIdsOf($this->id))
            ->orderBy('published_at', 'desc')
            ->orderBy('title')
            ->orderBy('id')
            ->findAll();

        return array_map(Entry::fromRow(...), $rows);
    }
}
