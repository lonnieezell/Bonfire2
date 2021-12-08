<?php

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Recycler\Config;

$routes->group(ADMIN_AREA, ['namespace' => '\Bonfire\Recycler\Controllers'], static function ($routes) {
    $routes->get('recycler', 'RecycleController::viewResource', ['as' => 'recycler']);
    $routes->get('recycler/restore/(:segment)/(:num)', 'RecycleController::restore/$1/$2');
    $routes->get('recycler/purge/(:segment)/(:num)', 'RecycleController::purge/$1/$2');
});
