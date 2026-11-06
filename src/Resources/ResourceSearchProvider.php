<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Resources;

use Bonfire\Search\Interfaces\SearchProviderInterface;

/**
 * Puts a resource's records in the admin's global search, found by the resource's `$search` columns.
 * `ResourceRegistry::register()` makes one for each resource that has `$search`.
 */
class ResourceSearchProvider implements SearchProviderInterface
{
    public function __construct(private readonly Resource $resource)
    {
    }

    /**
     * Whether the signed-in user may see the resource's list, which is what its records in the search require.
     */
    public function isAllowed(): bool
    {
        return $this->resource->allows('list') && auth()->user() !== null && auth()->user()->can($this->resource->permission . '.list');
    }

    /**
     * The records where a `$search` column contains the term, as a label and the link to the list narrowed to it.
     *
     * @return list<array{label: string, url: string}>
     */
    public function search(string $term, int $limit = 10, ?array $post = null): array
    {
        if (! $this->isAllowed() || $this->resource->search === []) {
            return [];
        }

        $model = $this->resource->query(model($this->resource->model, false));

        // The first search column names a record; searching for it finds the record again.
        $handle  = $this->resource->search[0];
        $records = $this->resource->applySearch($model, $term)
            ->orderBy($model->table . '.' . $model->primaryKey, 'asc')
            ->findAll($limit);

        return array_map(function (mixed $record) use ($handle, $model, $term): array {
            $label = trim((string) (is_array($record) ? ($record[$handle] ?? '') : ($record->{$handle} ?? '')));

            // A record without a name is labelled by its key, which the list can't search for, so its link keeps the term.
            $search = $label === '' ? trim($term) : $label;

            if ($label === '') {
                $label = (string) (is_array($record) ? $record[$model->primaryKey] : $record->{$model->primaryKey});
            }

            return [
                'label' => $label,
                'url'   => url_to($this->resource->slug . '-list') . '?' . http_build_query(['search' => $search]),
            ];
        }, $records);
    }

    public function resourceName(): string
    {
        return $this->resource->title();
    }

    public function resourceUrl(): string
    {
        return ADMIN_AREA . '/' . $this->resource->slug;
    }

    /**
     * In the package, not the Admin theme, so it reaches installs that published their own copy of the theme.
     */
    public function resultView(): string
    {
        return 'Bonfire\Resources\Views\search_results';
    }
}
