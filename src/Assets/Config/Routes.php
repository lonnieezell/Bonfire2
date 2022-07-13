<?php

/**
 * @var CodeIgniter\Router\RouteCollection $routes
 */
$routes->get('assets/(:any)', '\Bonfire\Assets\Controllers\AssetController::serve/$1');
