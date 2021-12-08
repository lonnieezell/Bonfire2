<?php

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Search\Config;

$routes->group(ADMIN_AREA, ['namespace' => '\Bonfire\Modules\Search\Controllers'], static function ($routes) {
    $routes->post('search', 'SearchController::overview', ['as' => 'search']);
});
