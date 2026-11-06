<?php

namespace Tests\Support\Modules\Products;

use Bonfire\Core\BaseModule;
use Tests\Support\Modules\Products\Catalog\CatalogResource;

/**
 * The Products resources, for the resource form tests. Discovered once a test calls withTestResources().
 */
class Module extends BaseModule
{
    public function initAdmin()
    {
        service('resources')->register(ProductResource::class);
        service('resources')->register(CatalogResource::class);
        service('resources')->register(ProductRowResource::class);
    }
}
