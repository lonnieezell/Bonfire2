<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
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
