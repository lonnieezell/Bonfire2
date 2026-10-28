<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
// Bonfire Admin routes
$routes->group(ADMIN_AREA, ['namespace' => '\Bonfire\Dashboard\Controllers'], static function ($routes) {
    $routes->get('/', 'DashboardController::index');
    $routes->post('dashboard/layout', 'DashboardController::saveLayout', ['as' => 'dashboard-layout', 'filter' => 'csrf']);
    $routes->post('dashboard/layout/default', 'DashboardController::saveSiteLayout', ['as' => 'dashboard-site-layout', 'filter' => 'csrf']);
});
