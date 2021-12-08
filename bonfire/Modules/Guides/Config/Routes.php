<?php

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Guides\Config;

$routes->group(ADMIN_AREA, ['namespace' => '\Bonfire\Guides\Controllers'], static function ($routes) {
    $routes->get('guides', 'GuidesController::viewCollections', ['as' => 'guides']);
    $routes->get('guides/(:segment)', 'GuidesController::viewCollection/$1', ['as' => 'view-guide-collection']);
    $routes->get('guides/(:segment)/(:any)', 'GuidesController::viewSingle/$1/$2', ['as' => 'view-guide']);
});
