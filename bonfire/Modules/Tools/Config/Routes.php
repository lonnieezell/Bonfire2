<?php

namespace Bonfire\Assets\Config;

$routes->get(ADMIN_AREA.'tools/system-info', '\Bonfire\Tools\Controllers\SystemInfoController::index', ['as' => 'sys-info']);
$routes->get(ADMIN_AREA.'tools/php-info', '\Bonfire\Tools\Controllers\SystemInfoController::phpInfo');
$routes->get(ADMIN_AREA.'tools/logs', '\Bonfire\Tools\Controllers\LogsController::index', ['as' => 'sys-logs']);
$routes->post(ADMIN_AREA.'tools/logs', '\Bonfire\Tools\Controllers\LogsController::index');
$routes->get(ADMIN_AREA.'tools/view-log/(:any)', '\Bonfire\Tools\Controllers\LogsController::view/$1', ['as' => 'view-log']);
