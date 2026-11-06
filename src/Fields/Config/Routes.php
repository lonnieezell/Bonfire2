<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->group(ADMIN_AREA . '/fields', ['namespace' => '\Bonfire\Fields\Controllers'], static function ($routes) {
    $routes->get('options', 'OptionsController::index', ['as' => 'fields-options']);
});
