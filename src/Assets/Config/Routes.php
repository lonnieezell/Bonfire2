<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('assets/(:any)', '\Bonfire\Assets\Controllers\AssetController::serve/$1');
