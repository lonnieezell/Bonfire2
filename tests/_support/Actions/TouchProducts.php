<?php

namespace Tests\Support\Actions;

use Bonfire\Actions\Action;
use Bonfire\Actions\ActionContext;
use Bonfire\Actions\ActionResult;

/**
 * A test action that another module adds to the Products list, which records the products it handled.
 */
class TouchProducts extends Action
{
    public string $label      = 'Touch';
    public string $permission = 'products.list';

    /**
     * @var list<int>
     */
    public array $touched = [];

    public function handle(ActionContext $context): ActionResult
    {
        foreach ($context->entities() as $product) {
            $this->touched[] = $product->id;
        }

        return ActionResult::message('{count} products touched.');
    }
}
