<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Modules\Tools\Config;

$routes->get(ADMIN_AREA . '/tools/system-info', '\Bonfire\Tools\Controllers\SystemInfoController::index', ['as' => 'sys-info']);
$routes->get(ADMIN_AREA . '/tools/php-info', '\Bonfire\Tools\Controllers\SystemInfoController::phpInfo');
$routes->match(['get', 'post'], ADMIN_AREA . '/tools/logs', '\Bonfire\Tools\Controllers\LogsController::index', ['as' => 'sys-logs']);
$routes->get(ADMIN_AREA . '/tools/view-log/(:segment)', '\Bonfire\Tools\Controllers\LogsController::view/$1', ['as' => 'view-log']);
$routes->post(ADMIN_AREA . '/tools/delete-log', '\Bonfire\Tools\Controllers\LogsController::delete', ['as' => 'log-delete']);
