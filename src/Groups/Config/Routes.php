<?php

use CodeIgniter\Router\RouteCollection;
/**
 * @var RouteCollection $routes
 */
$routes->group(ADMIN_AREA, ['namespace' => '\Bonfire\Groups\Controllers'], static function ($routes) {
    $routes->get('settings/groups', 'GroupSettingsController::list', ['as' => 'user-group-settings']);
    $routes->post('settings/groups', 'GroupSettingsController::save');
    $routes->get('settings/groups/(:segment)', 'GroupSettingsController::show/$1');
    $routes->post('settings/groups/(:segment)', 'GroupSettingsController::save/$1');
    $routes->get('settings/groups/(:segment)/permissions', 'GroupSettingsController::permissions/$1');
    $routes->post('settings/groups/(:segment)/permissions', 'GroupSettingsController::savePermissions/$1');
});
