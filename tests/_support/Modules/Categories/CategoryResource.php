<?php

namespace Tests\Support\Modules\Categories;

use Bonfire\Fields\Field;
use Bonfire\Resources\Resource;
use Tests\Support\Models\CategoryModel;

/**
 * A resource with nothing to it but its name, as a developer would write one.
 */
class CategoryResource extends Resource
{
    public string $model      = CategoryModel::class;
    public string $slug       = 'categories';
    public string $permission = 'categories';
    public array $pages       = ['list'];
    public array $search      = ['name', 'status'];

    public function fields(): array
    {
        return [new Field('name', 'text', 'Name', 'required', null, [])];
    }

    public function filters(): array
    {
        return [
            'status' => ['title' => 'Status', 'options' => ['live' => 'Live', 'draft' => 'Draft', 'retired' => 'Retired']],
            'name'   => ['title' => 'Letter', 'options' => 'letters'],
        ];
    }

    /**
     * The options of a filter that a method computes.
     *
     * @return array<string, string>
     */
    public function letters(): array
    {
        return ['Alpha' => 'Alpha', 'Bravo' => 'Bravo'];
    }
}
