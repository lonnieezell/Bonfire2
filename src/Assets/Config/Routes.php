<?php

declare(strict_types=1);

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('assets/(:any)', '\Bonfire\Assets\Controllers\AssetController::serve/$1');
