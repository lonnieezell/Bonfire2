<?php

namespace Tests\Support\Modules\Products;

use Bonfire\Resources\Fields\Text;
use Bonfire\Resources\Resource;
use Tests\Support\Models\ProductModel;

/**
 * A resource with a mistake: `notes` isn't in the model's $allowedFields. Not registered by the Module.
 */
class StrayResource extends Resource
{
    public string $model      = ProductModel::class;
    public string $slug       = 'strays';
    public string $permission = 'products';
    public array $pages       = ['list', 'create', 'edit'];

    public function fields(): array
    {
        return [Text::make('name'), Text::make('notes')];
    }
}
