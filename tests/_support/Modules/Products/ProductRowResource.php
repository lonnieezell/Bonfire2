<?php

namespace Tests\Support\Modules\Products;

use Bonfire\Resources\Fields\Select;
use Bonfire\Resources\Fields\Text;
use Bonfire\Resources\Resource;
use Tests\Support\Models\ProductArrayModel;

/**
 * Products again, over a model that returns arrays instead of entities.
 */
class ProductRowResource extends Resource
{
    public string $model      = ProductArrayModel::class;
    public string $slug       = 'product-rows';
    public string $permission = 'products';
    public array $pages       = ['list', 'create', 'edit'];

    /**
     * @var list<mixed>
     */
    public static array $saved = [];

    public function fields(): array
    {
        return [
            Text::make('name')->validate('required'),
            Select::make('status')->options(['draft', 'active']),
        ];
    }

    public function afterSave(mixed $record): void
    {
        self::$saved[] = $record;
    }
}
