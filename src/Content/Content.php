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
     * Finds entries by their UUIDs, in the order given. An id with no entry, or whose entry is in the trash, is skipped.
     *
     * @param list<string> $ids
     *
     * @return list<Entry>
     */
    public function findMany(array $ids): array
    {
        $ids = array_values(array_unique(array_map(strtolower(...), $ids)));

        if ($ids === []) {
            return [];
        }

        $rows    = array_column((new ContentEntryModel())->whereIn('id', $ids)->findAll(), null, 'id');
        $entries = [];

        foreach ($ids as $id) {
            if (isset($rows[$id])) {
                $entries[] = Entry::fromRow($rows[$id]);
            }
        }

        return $entries;
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
     * A navigation menu as a tree of `title`, `url`, `active` and `children`. An item for an entry takes its
     * title and URL from the entry, so a renamed slug changes the URL. An item for an entry that isn't published,
     * or is in the trash, is left out with the items below it. A tree file that can't be read is logged, and gives no items.
     *
     * @param string|null $currentPath the path of the page being shown, which is the request's unless given
     *
     * @return list<array{title: string, url: string|null, active: bool, children: list<mixed>}>
     *
     * @throws ContentException when the navigation doesn't exist or its config is invalid
     */
    public function navigation(string $handle, ?string $currentPath = null): array
    {
        $navigation = Navigation::load($this->contentRoot, $handle);

        try {
            $tree = NavigationTree::parse($navigation->rawTree($this->contentRoot));
        } catch (ContentException $e) {
            log_message('error', 'Content: navigation tree {handle}: {message}', ['handle' => $handle, 'message' => $e->getMessage()]);

            return [];
        }

        $ids     = $tree->entryIds();
        $entries = $ids === [] ? [] : array_column((new ContentEntryModel())->published()->select('id, title, uri')->whereIn('id', $ids)->findAll(), null, 'id');

        return $tree->items($entries, $currentPath ?? service('request')->getPath());
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
