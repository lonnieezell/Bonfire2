<?php

namespace Tests\Support\Modules\Products;

use Bonfire\Actions\Action;
use Bonfire\Actions\ActionContext;
use Bonfire\Actions\ActionResult;
use Bonfire\Users\User;
use Tests\Support\Models\ProductModel;

/**
 * A custom action of the Products resource: features the selected products. An archived product can't be featured.
 */
class FeatureProducts extends Action
{
    public string $label      = 'Feature';
    public string $icon       = 'fas fa-star';
    public string $permission = 'products.feature';

    public function allows(User $actor, object $record): bool
    {
        return $record->status !== 'archived';
    }

    public function handle(ActionContext $context): ActionResult
    {
        foreach ($context->entities() as $product) {
            model(ProductModel::class)->update($product->id, ['featured' => 1]);
        }

        return ActionResult::message('{count} products featured.');
    }
}
