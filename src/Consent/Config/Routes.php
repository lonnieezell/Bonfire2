<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->group(ADMIN_AREA, ['namespace' => '\Bonfire\Consent\Controllers'], static function ($routes) {
    $routes->get('settings/consent', 'ConsentSettingsController::index', ['as' => 'consent-settings']);
    $routes->post('settings/consent', 'ConsentSettingsController::save');
});

$routes->post('consent', '\Bonfire\Consent\Controllers\ConsentController::save', ['as' => 'consent-save']);
