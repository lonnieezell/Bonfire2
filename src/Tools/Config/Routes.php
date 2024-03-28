<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->group(ADMIN_AREA . '/tools', ['namespace' => '\Bonfire\Tools\Controllers'], static function ($routes) {
    $routes->get('system-info', '\Bonfire\Tools\Controllers\SystemInfoController::index', ['as' => 'sys-info']);
    $routes->get('php-info', '\Bonfire\Tools\Controllers\SystemInfoController::phpInfo');
    $routes->match(['get', 'post'], 'logs', '\Bonfire\Tools\Controllers\LogsController::index', ['as' => 'sys-logs']);
    $routes->get('view-log/(:segment)', '\Bonfire\Tools\Controllers\LogsController::view/$1', ['as' => 'view-log']);
    $routes->post('delete-log', '\Bonfire\Tools\Controllers\LogsController::delete', ['as' => 'log-delete']);
});

$routes->get('render-test', static function () {
    $data = [
        'title'   => 'Test',
        'content' => 'This is a test',
    ];

    return render('admin', '\Bonfire\Tools\Views\index', $data);
});
