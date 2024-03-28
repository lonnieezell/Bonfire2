<?php

use CodeIgniter\Router\RouteCollection;
/**
 * @var RouteCollection $routes
 */
$routes->group(ADMIN_AREA . '/settings', ['namespace' => '\Bonfire\Settings\Controllers'], static function ($routes) {
    $routes->get('general', 'GeneralSettingsController::general', ['as' => 'general-settings']);
    $routes->post('general', 'GeneralSettingsController::saveGeneral');

    $routes->get('timezones', 'GeneralSettingsController::getTimezones');
});
