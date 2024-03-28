<?php

use CodeIgniter\Router\RouteCollection;
/**
 * @var RouteCollection $routes
 */
$routes->group(ADMIN_AREA . '/search', ['namespace' => '\Bonfire\Search\Controllers'], static function ($routes) {
    $routes->post('/', 'SearchController::overview', ['as' => 'search']);
});
