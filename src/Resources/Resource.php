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

use Bonfire\Core\Like;
use Bonfire\Fields\Field;
use Bonfire\Fields\FieldTypes;
use Bonfire\Fields\Types\TextareaField;
use CodeIgniter\Model;

/**
 * Describes a model for the admin: where it lives, who may use it and what the list shows.
 * Extend it, then register the class with `service('resources')->register()` in a module's
 * initAdmin() and declare its routes with `resource_routes()` in the module's Config/Routes.php.
 */
abstract class Resource
{
    /**
     * The model that holds the records.
     *
     * @var class-string<Model>
     */
    public string $model;

    /**
     * The URL segment after the admin area, and the prefix of the route names.
     */
    public string $slug;

    /**
     * The prefix of the permissions that guard the pages, such as `categories` for `categories.list`.
     */
    public string $permission;

    /**
     * The pages the resource has. Only `list` exists so far, and the routes and buttons of a page
     * that isn't named here are left out.
     *
     * @var list<string>
     */
    public array $pages = ['list'];

    /**
     * The columns of the model's table that the list's search box and the admin's global search look in.
     * A record matches when any of them contains the search. Leave it empty for a resource without search.
     *
     * @var list<string>
     */
    public array $search = [];

    /**
     * The name in the sidebar and above the list.
     */
    public function title(): string
    {
        return ucfirst(str_replace(['-', '_'], ' ', $this->slug));
    }

    /**
     * The fields of a record.
     *
     * @return list<Field>
     */
    public function fields(): array
    {
        return [];
    }

    /**
     * The columns of the list: the fields, except those that hold long text, which don't fit a table cell.
     * A column that is a column of the model's table can be sorted.
     *
     * @return list<Field>
     */
    public function columns(): array
    {
        return array_values(array_filter(
            $this->fields(),
            static fn (Field $field): bool => ! FieldTypes::get($field->type) instanceof TextareaField,
        ));
    }

    /**
     * The filters of the list, in the shape of a model's `$filters` (see Filterable): the key is a column of
     * the model's table, and each is an array with a `title`, the `options` and, optionally, a `type` of `radio`.
     * A string for `options` names a method of the resource that returns them. A record matches a filter when
     * its column holds any of the chosen options.
     *
     * @return array<string, array{title: string, options: array<int|string, string>|string, type?: string}>
     */
    public function filters(): array
    {
        return [];
    }

    /**
     * The filters, with the options that a method computes filled in.
     *
     * @return array<string, array{title: string, options: array<int|string, string>, type?: string}>
     */
    public function getFilters(): array
    {
        $filters = $this->filters();

        foreach ($filters as &$filter) {
            if (is_string($filter['options'])) {
                $filter['options'] = $this->{$filter['options']}();
            }
        }

        return $filters;
    }

    /**
     * Prepares the model before the list adds its sorting and paging, for joins and eager loads.
     */
    public function query(Model $model): Model
    {
        return $model;
    }

    /**
     * Narrows the model to the records where any `$search` column contains the term.
     */
    public function applySearch(Model $model, string $term): Model
    {
        $term = trim($term);

        if ($term === '' || $this->search === []) {
            return $model;
        }

        $needle = Like::escape($term, $model->db);

        $model->groupStart();

        foreach ($this->search as $column) {
            // like() quotes the column without adding the table prefix, so this does it.
            $model->orLike($model->db->protectIdentifiers($this->qualify($model, $column)), $needle, 'both', true, true);
        }

        return $model->groupEnd();
    }

    /**
     * Narrows the model to the records that match the chosen options of each filter. Only the columns that
     * `filters()` names count, and a filter with nothing chosen leaves the list as it is.
     *
     * @param array<string, mixed> $chosen the options by column, as the filter form sends them
     */
    public function applyFilters(Model $model, array $chosen): Model
    {
        foreach (array_keys($this->filters()) as $column) {
            $options = array_values(array_filter(
                (array) ($chosen[$column] ?? []),
                static fn (mixed $option): bool => is_scalar($option) && $option !== '',
            ));

            if ($options !== []) {
                $model->whereIn($this->qualify($model, $column), $options);
            }
        }

        return $model;
    }

    public function allows(string $page): bool
    {
        return in_array($page, $this->pages, true);
    }

    /**
     * A column of the model's table named with the table, so that a join in query() can't make it ambiguous.
     */
    private function qualify(Model $model, string $column): string
    {
        return str_contains($column, '.') ? $column : $model->table . '.' . $column;
    }
}
