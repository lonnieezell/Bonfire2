<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

$routes->group(ADMIN_AREA . '/settings', ['namespace' => '\Bonfire\Modules\Settings\Controllers'], static function ($routes) {
    $routes->get('general', 'GeneralSettingsController::general', ['as' => 'general-settings']);
    $routes->post('general', 'GeneralSettingsController::saveGeneral');

    $routes->get('timezones', 'GeneralSettingsController::getTimezones');
});
