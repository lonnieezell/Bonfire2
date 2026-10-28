<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->group(ADMIN_AREA . '/actions', ['namespace' => '\Bonfire\Actions\Controllers'], static function ($routes) {
    $routes->get('runs/(:num)', 'ActionController::progress/$1', ['as' => 'action-run']);
    // CSRF is checked here even when an app doesn't turn it on globally.
    $routes->post('(:segment)/(:segment)', 'ActionController::run/$1/$2', ['as' => 'action', 'filter' => 'csrf']);
});
