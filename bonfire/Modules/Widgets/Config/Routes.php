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

$routes->group(ADMIN_AREA, ['namespace' => '\Bonfire\Widgets\Controllers'], static function ($routes) {
    $routes->get('settings/widgets', 'WidgetsSettingsController::index', ['as' => 'widgets-settings']);
    $routes->get('settings/widgets/(:segment)', 'WidgetsSettingsController::show/$1');
    $routes->post('settings/widgets', 'WidgetsSettingsController::save');
    $routes->post('settings/widgetsReset', 'WidgetsSettingsController::resetSettings');

    $routes->post('settings/widgets/schemePreview', 'WidgetsSettingsController::getColorSchemePreview');
});
