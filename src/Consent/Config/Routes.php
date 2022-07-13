<?php
/**
 * @var CodeIgniter\Router\RouteCollection $routes
 */

$routes->group(ADMIN_AREA, ['namespace' => '\Bonfire\Consent\Controllers'], static function ($routes) {
    $routes->get('settings/consent', 'ConsentSettingsController::index', ['as' => 'consent-settings']);
    $routes->post('settings/consent', 'ConsentSettingsController::save');
});
