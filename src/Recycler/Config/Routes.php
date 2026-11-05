<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->group(ADMIN_AREA . '/tools', ['namespace' => '\Bonfire\Recycler\Controllers'], static function ($routes) {
    $routes->get('recycler', 'RecycleController::viewResource', ['as' => 'recycler']);
    $routes->get('recycler/restore/(:segment)/(:segment)', 'RecycleController::restore/$1/$2', ['as' => 'recycler-restore']);
    $routes->get('recycler/purge/(:segment)/(:segment)', 'RecycleController::purge/$1/$2', ['as' => 'recycler-purge']);
});
