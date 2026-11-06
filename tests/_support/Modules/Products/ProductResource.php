<?php

namespace Tests\Support\Modules\Products;

use Bonfire\Resources\Actions\DeleteRecords;
use Bonfire\Resources\Column;
use Bonfire\Resources\Fields\BelongsTo;
use Bonfire\Resources\Fields\Date;
use Bonfire\Resources\Fields\Datetime;
use Bonfire\Resources\Fields\Decimal;
use Bonfire\Resources\Fields\Integer;
use Bonfire\Resources\Fields\Markdown;
use Bonfire\Resources\Fields\Section;
use Bonfire\Resources\Fields\Select;
use Bonfire\Resources\Fields\Text;
use Bonfire\Resources\Fields\Textarea;
use Bonfire\Resources\Fields\Toggle;
use Bonfire\Resources\Resource;
use CodeIgniter\Model;
use Tests\Support\Models\CategoryModel;
use Tests\Support\Models\ProductModel;

/**
 * The first resource: a field of every v1 type, in sections, with hooks, a per-record rule and a list that
 * joins the category.
 */
class ProductResource extends Resource
{
    public string $model      = ProductModel::class;
    public string $slug       = 'products';
    public string $permission = 'products';
    public array $pages       = ['list', 'create', 'edit'];

    /**
     * What afterSave() was given, in order.
     *
     * @var list<mixed>
     */
    public static array $saved = [];

    public function fields(): array
    {
        return [
            Section::make('Basics', [
                Text::make('name'),
                Text::make('sku')->label('SKU')->validate('required|alpha_dash|is_unique[products.sku,id,{id}]'),
                Textarea::make('summary')->rows(3),
                Markdown::make('description'),
            ])->columns(2),
            Section::make('Pricing and stock', [
                Decimal::make('price')->places(2)->min(0),
                Integer::make('stock')->min(0),
                BelongsTo::make('category_id')->label('Category')->model(CategoryModel::class)->title('name'),
            ])->instructions('What it costs, and how many there are.')->columns(3),
            Section::make('Publishing', [
                Select::make('status')->options(['draft' => 'Draft', 'active' => 'Active', 'archived' => 'Archived'])->validate('required'),
                Toggle::make('featured'),
                Date::make('released_on'),
                Datetime::make('available_at'),
            ]),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('name'),
            Column::make('sku', 'SKU'),
            Column::make('category.name', 'Category'),
            Column::make('stock_value', 'Stock value')->using(static fn ($product): string => $product->price !== null && $product->stock !== null
                ? number_format((float) $product->price * (int) $product->stock, 2, '.', '')
                : ''),
            Column::make('status'),
        ];
    }

    /**
     * One query for the list: the category's name comes with each product.
     */
    public function query(Model $model): Model
    {
        return $model->select('products.*, categories.name AS category_name')->join('categories', 'categories.id = products.category_id', 'left');
    }

    public function actions(): array
    {
        return [new DeleteRecords($this), new FeatureProducts()];
    }

    /**
     * An archived product is read-only, and can't be deleted.
     */
    public function can(string $verb, mixed $entity = null): bool
    {
        if (in_array($verb, ['edit', 'delete'], true) && $entity !== null && $entity->status === 'archived') {
            return false;
        }

        return parent::can($verb, $entity);
    }

    /**
     * Adds a slug, which is no field, and drops `featured` for a product that isn't active.
     */
    public function beforeSave(array $data, mixed $record): array
    {
        helper('url');

        $data['slug'] = url_title((string) ($data['name'] ?? ''), '-', true);

        if (($data['status'] ?? '') !== 'active') {
            unset($data['featured']);
        }

        return $data;
    }

    public function afterSave(mixed $record): void
    {
        self::$saved[] = $record;
    }
}
