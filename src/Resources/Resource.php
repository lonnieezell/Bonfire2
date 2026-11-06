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
     * Prepares the model before the list adds its sorting and paging, for joins and eager loads.
     */
    public function query(Model $model): Model
    {
        return $model;
    }

    public function allows(string $page): bool
    {
        return in_array($page, $this->pages, true);
    }
}
