<?php

namespace Tests\Support\Modules\Categories;

use Bonfire\Core\BaseModule;
use Tests\Support\Modules\Categories\Shelves\ShelfResource;

/**
 * The Categories resource, for the resource tests. Discovered once a test calls withTestResources().
 */
class Module extends BaseModule
{
    public function initAdmin()
    {
        service('resources')->register(CategoryResource::class);
        service('resources')->register(ShelfResource::class);
    }
}
