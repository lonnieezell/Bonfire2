<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->group(ADMIN_AREA . '/tools', ['namespace' => '\Bonfire\Recycler\Controllers'], static function ($routes) {
    $routes->get('recycler', 'RecycleController::viewResource', ['as' => 'recycler']);
    // CSRF is checked here even when an app doesn't turn it on globally.
    $routes->post('recycler/restore/(:segment)/(:segment)', 'RecycleController::restore/$1/$2', ['as' => 'recycler-restore', 'filter' => 'csrf']);
    $routes->post('recycler/purge/(:segment)/(:segment)', 'RecycleController::purge/$1/$2', ['as' => 'recycler-purge', 'filter' => 'csrf']);
});
