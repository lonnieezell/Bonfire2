<?php

use CodeIgniter\Router\RouteCollection;
use Tests\Support\Modules\Categories\CategoryResource;
use Tests\Support\Modules\Categories\Shelves\ShelfResource;
use Tests\Support\Modules\Products\Catalog\CatalogResource;
use Tests\Support\Modules\Products\ProductResource;
use Tests\Support\Modules\Products\ProductRowResource;

/** @var RouteCollection $routes */

// A resource's routes line. Its Module is only discovered once a test calls withTestResources().
resource_routes($routes, CategoryResource::class);
resource_routes($routes, ShelfResource::class);
resource_routes($routes, ProductResource::class);
resource_routes($routes, CatalogResource::class);
resource_routes($routes, ProductRowResource::class);
