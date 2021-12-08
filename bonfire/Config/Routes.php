<?php

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

$routes->get('site-offline', static function () {
    echo view('errors/html/offline.php');
});

// Bonfire Admin routes
$routes->group(ADMIN_AREA, ['namespace' => '\Bonfire\Controllers'], static function ($routes) {
    $routes->get('/', 'Dashboard::index');
});
