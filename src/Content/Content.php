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
     * A taxonomy's terms, by title, each with the number of published entries that have it.
     *
     * @return list<Term>
     *
     * @throws ContentException when the taxonomy doesn't exist or its config is invalid
     */
    public function terms(string $taxonomy): array
    {
        $taxonomy = Taxonomy::load($this->contentRoot, $taxonomy);
        $rows     = (new ContentTermModel())->inTaxonomy($taxonomy->handle);
        $counts   = $this->countEntries(array_column($rows, 'id'));

        return array_map(static fn (array $row): Term => Term::fromRow($row, $counts[$row['id']] ?? 0), $rows);
    }

    /**
     * Finds a term by its slug, with the number of published entries that have it.
     *
     * @throws ContentException when the taxonomy doesn't exist or its config is invalid
     */
    public function term(string $taxonomy, string $slug): ?Term
    {
        $row = (new ContentTermModel())->lookup(Taxonomy::load($this->contentRoot, $taxonomy)->handle, $slug);

        return $row === null ? null : Term::fromRow($row, $this->countEntries([$row['id']])[$row['id']] ?? 0);
    }

    /**
     * Finds an entry by its UUID, in any collection and whether or not it's published.
     */
    public function find(string $id): ?Entry
    {
        $row = (new ContentEntryModel())->find(strtolower($id));

        return $row === null ? null : Entry::fromRow($row);
    }

    /**
     * Finds the published entry at a URI. Drafts and scheduled entries are treated as missing.
     */
    public function findByUri(string $uri): ?Entry
    {
        $uri = Collection::normalizeUri($uri);
        $row = (new ContentEntryModel())->published()->where('uri', $uri)->first();

        // A case-insensitive collation also matches other spellings of the URI.
        return $row === null || $row['uri'] !== $uri ? null : Entry::fromRow($row);
    }

    /**
     * @param list<string> $termIds
     *
     * @return array<string, int> the published entries of each term that has any, by term id
     */
    private function countEntries(array $termIds): array
    {
        $counts = [];

        foreach (array_chunk($termIds, TermIndex::CHUNK) as $chunk) {
            $rows = (new ContentEntryModel())
                ->published()
                ->select('content_entry_terms.term_id AS term_id, COUNT(*) AS entries')
                ->join('content_entry_terms', 'content_entry_terms.entry_id = content_entries.id')
                ->whereIn('content_entry_terms.term_id', $chunk)
                ->groupBy('content_entry_terms.term_id')
                ->findAll();

            foreach ($rows as $row) {
                $counts[$row['term_id']] = (int) $row['entries'];
            }
        }

        return $counts;
    }
}
