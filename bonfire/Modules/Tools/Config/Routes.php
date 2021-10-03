<?php

namespace Bonfire\Assets\Config;

$routes->get(ADMIN_AREA.'tools/system-info', '\Bonfire\Tools\Controllers\SystemInfoController::index', ['as' => 'sys-info']);
$routes->get(ADMIN_AREA.'/tools/php-info', '\Bonfire\Tools\Controllers\SystemInfoController::phpInfo');
