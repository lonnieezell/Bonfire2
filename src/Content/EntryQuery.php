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
use CodeIgniter\Pager\Pager;

/**
 * A query on one collection's entries, ordered by the collection's sort unless
 * orderBy() says otherwise.
 */
final class EntryQuery
{
    private bool $published = false;

    /**
     * @var list<array{string, string}>
     */
    private array $order = [];

    private ?Pager $pager = null;

    public function __construct(private readonly Collection $collection)
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
        $model->where('collection', $this->collection->handle);

        if ($this->published) {
            $model->published();
        }

        return $model;
    }

    /**
     * A model with the final order. Entries sharing a value are ordered by id, so that pages can't overlap.
     */
    private function ordered(): ContentEntryModel
    {
        $model = $this->model();

        foreach ($this->order ?: [[$this->collection->sortField, $this->collection->sortDirection]] as [$field, $direction]) {
            $model->orderBy($field, $direction);
        }

        return $model->orderBy('id');
    }
}
