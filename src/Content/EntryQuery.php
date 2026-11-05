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
use CodeIgniter\Pager\Pager;

/**
 * A query on one collection's entries, ordered by the collection's sort unless
 * orderBy() says otherwise. A search, from content()->search(), covers every collection or the one it was narrowed to,
 * and is ordered by title.
 */
final class EntryQuery
{
    private bool $published = false;

    /**
     * @var list<array{string, string}>
     */
    private array $order = [];

    /**
     * @var list<array{string, string}> the taxonomy and slug of each term asked for
     */
    private array $terms = [];

    private ?Pager $pager = null;

    /**
     * @param Collection|null $collection null for a search across every collection
     * @param string|null     $search     keeps entries with this text in the title or body
     */
    public function __construct(private readonly ?Collection $collection, private readonly ?string $search = null)
    {
    }

    /**
     * Keeps entries that are published and whose published_at, if any, has passed.
     */
    public function published(): self
    {
        $this->published = true;

        return $this;
    }

    /**
     * Keeps published entries that have a term, as published() does. Calling it again keeps only
     * entries that have every term asked for. A term that doesn't exist matches nothing.
     *
     * @param string $taxonomy a taxonomy the collection uses
     * @param string $slug     the term's slug
     *
     * @throws ContentException when the collection doesn't use the taxonomy, or the query has no collection
     */
    public function whereTerm(string $taxonomy, string $slug): self
    {
        if ($this->collection === null) {
            throw new ContentException('whereTerm() needs a query on one collection. Narrow the search to a collection, or start with content()->collection().');
        }

        if (! in_array($taxonomy, $this->collection->taxonomies, true)) {
            throw new ContentException("Collection {$this->collection->handle} doesn't use the taxonomy '{$taxonomy}'. Add it to the collection's `taxonomies`.");
        }

        $this->terms[] = [$taxonomy, $slug];

        return $this->published();
    }

    /**
     * @param string $field one of Collection::SORTABLE. Front matter fields aren't indexed.
     *
     * @throws ContentException
     */
    public function orderBy(string $field, string $direction = 'asc'): self
    {
        Collection::assertSort($field, $direction);

        $this->order[] = [$field, $direction];

        return $this;
    }

    /**
     * @return list<Entry>
     */
    public function get(): array
    {
        return array_map(Entry::fromRow(...), $this->ordered()->findAll());
    }

    /**
     * @param int|null $page null takes the page from the request
     *
     * @return list<Entry>
     */
    public function paginate(int $perPage = 20, string $group = 'default', ?int $page = null): array
    {
        $model = $this->ordered();
        $rows  = $model->paginate($perPage, $group, $page);

        $this->pager = $model->pager;

        return array_map(Entry::fromRow(...), $rows);
    }

    /**
     * The pager of the last paginate() call.
     */
    public function pager(): ?Pager
    {
        return $this->pager;
    }

    /**
     * Finds an entry by its UUID, within this query's conditions.
     */
    public function find(string $id): ?Entry
    {
        $row = $this->model()->where('id', strtolower($id))->first();

        return $row === null ? null : Entry::fromRow($row);
    }

    /**
     * A new model each time, because a model forgets its conditions when it runs a query.
     */
    private function model(): ContentEntryModel
    {
        $model = new ContentEntryModel();

        if ($this->collection !== null) {
            $model->where('collection', $this->collection->handle);
        }

        if ($this->published) {
            $model->published();
        }

        if ($this->search !== null) {
            $model->matching($this->search);
        }

        foreach ($this->terms as [$taxonomy, $slug]) {
            $term = (new ContentTermModel())->lookup($taxonomy, $slug);

            if ($term === null) {
                $model->where('1 = 0', null, false);
            } else {
                $model->whereIn('id', TermIndex::entryIdsOf($term['id']));
            }
        }

        return $model;
    }

    /**
     * A model with the final order. Entries sharing a value are ordered by id, so that pages can't overlap.
     */
    private function ordered(): ContentEntryModel
    {
        $model = $this->model();

        $default = $this->search !== null || $this->collection === null
            ? ['title', 'asc']
            : [$this->collection->sortField, $this->collection->sortDirection];

        foreach ($this->order ?: [$default] as [$field, $direction]) {
            $model->orderBy($field, $direction);
        }

        return $model->orderBy('id');
    }
}
