<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->group(ADMIN_AREA . '/actions', ['namespace' => '\Bonfire\Actions\Controllers'], static function ($routes) {
    $routes->get('runs/(:num)', 'ActionController::progress/$1', ['as' => 'action-run']);
    // Only the empty fields, so no record IDs travel in a URL.
    $routes->get('(:segment)/(:segment)/fields', 'ActionController::fields/$1/$2', ['as' => 'action-fields']);
    // CSRF is checked here even when an app doesn't turn it on globally.
    $routes->post('(:segment)/(:segment)', 'ActionController::run/$1/$2', ['as' => 'action', 'filter' => 'csrf']);
});
