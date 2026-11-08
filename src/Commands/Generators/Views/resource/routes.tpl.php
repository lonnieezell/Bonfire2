<@php

use <?= $namespace ?>\<?= $class ?>;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
resource_routes($routes, <?= $class ?>::class);
