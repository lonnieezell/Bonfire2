<?php

use CodeIgniter\Router\RouteCollection;
use Tests\Support\Modules\Categories\CategoryResource;
use Tests\Support\Modules\Categories\Shelves\ShelfResource;

/** @var RouteCollection $routes */

// A resource's routes line. Its Module is only discovered once a test calls withTestResources().
resource_routes($routes, CategoryResource::class);
resource_routes($routes, ShelfResource::class);
