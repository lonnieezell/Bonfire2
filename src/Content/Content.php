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
use CodeIgniter\HTTP\ResponseInterface;

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
     * Starts a query on the published entries whose title or Markdown body contain the term, in every collection or
     * only the one named. It's ordered by title, and paginate() and pager() work on it as on any query.
     * Bonfire adds no search page, so build yours around this.
     *
     * @throws ContentException when the collection doesn't exist or its config is invalid
     */
    public function search(string $term, ?string $collection = null): EntryQuery
    {
        $collection = $collection === null ? null : Collection::load($this->contentRoot, $collection);

        return (new EntryQuery($collection, $term))->published();
    }

    /**
     * An RSS feed of a collection's published entries, in the collection's order. An entry with no URI is left out.
     * Return it from a route of your own.
     *
     * @throws ContentException when the collection doesn't exist or its config is invalid
     */
    public function feed(string $handle): ResponseInterface
    {
        $collection = Collection::load($this->contentRoot, $handle);
        $entries    = (new EntryQuery($collection))->published()->get();

        return service('response')
            ->setContentType(Feed::CONTENT_TYPE, 'UTF-8')
            ->setBody(Feed::rss($collection, $entries));
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
     * A menu as a site shows it. An item whose entry can't be shown is left out, along with the items below it.
     * A tree file that can't be read is logged and gives an empty menu.
     *
     * @return list<NavigationItem>
     *
     * @throws ContentException when the menu doesn't exist or its config is invalid
     */
    public function navigation(string $handle): array
    {
        $navigation = Navigation::load($this->contentRoot, $handle);

        try {
            $tree = NavigationTree::parse(NavigationTree::raw($this->contentRoot, $navigation->handle));
        } catch (ContentException $e) {
            // A menu on every page shouldn't turn a bad edit of its file into a site that's down.
            log_message('error', 'Content: navigation {menu}: {message}', ['menu' => "{$navigation->handle}.yaml", 'message' => $e->getMessage()]);

            return [];
        }

        $ids     = $tree->entryIds();
        $entries = $ids === [] ? [] : (new ContentEntryModel())->published()->select('id, title, uri')->whereIn('id', $ids)->findAll();

        return $tree->items(array_column($entries, null, 'id'), Collection::normalizeUri(rawurldecode(service('request')->getPath())));
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
