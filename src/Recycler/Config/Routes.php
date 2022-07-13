<?php
/**
 * @var CodeIgniter\Router\RouteCollection $routes
 */

$routes->group(ADMIN_AREA, ['namespace' => '\Bonfire\Recycler\Controllers'], static function ($routes) {
    $routes->get('recycler', 'RecycleController::viewResource', ['as' => 'recycler']);
    $routes->get('recycler/restore/(:segment)/(:num)', 'RecycleController::restore/$1/$2');
    $routes->get('recycler/purge/(:segment)/(:num)', 'RecycleController::purge/$1/$2');
});
