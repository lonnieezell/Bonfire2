<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Consent\Config;

$routes->group(ADMIN_AREA, ['namespace' => '\Bonfire\Consent\Controllers'], static function ($routes) {
    $routes->get('settings/consent', 'ConsentSettingsController::index', ['as' => 'consent-settings']);
    $routes->post('settings/consent', 'ConsentSettingsController::save');
});
