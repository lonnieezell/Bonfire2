<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->group(ADMIN_AREA . '/docs', ['namespace' => '\Bonfire\Docs\Controllers'], static function ($routes) {
    $routes->get('/', 'DocsController::show', ['as' => 'docs']);
    $routes->get('(:any)', 'DocsController::show/$1');
});
