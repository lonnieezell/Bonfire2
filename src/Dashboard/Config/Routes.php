<?php

use CodeIgniter\Router\RouteCollection;
/**
 * @var RouteCollection $routes
 */
// Bonfire Admin routes
$routes->group(ADMIN_AREA, ['namespace' => '\Bonfire\Dashboard\Controllers'], static function ($routes) {
    $routes->get('/', 'DashboardController::index');
});
