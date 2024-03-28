<?php

use CodeIgniter\Router\RouteCollection;
/**
 * @var RouteCollection $routes
 */
$routes->group(ADMIN_AREA, ['namespace' => '\Bonfire\Widgets\Controllers'], static function ($routes) {
    $routes->get('settings/widgets', 'WidgetsSettingsController::index', ['as' => 'widgets-settings']);
    $routes->get('settings/widgets/(:segment)', 'WidgetsSettingsController::show/$1');
    $routes->post('settings/widgets', 'WidgetsSettingsController::save');
    $routes->post('settings/widgetsReset', 'WidgetsSettingsController::resetSettings');

    $routes->post('settings/widgets/schemePreview', 'WidgetsSettingsController::getColorSchemePreview');
});
