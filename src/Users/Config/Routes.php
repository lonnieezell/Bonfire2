<?php
/**
 * @var CodeIgniter\Router\RouteCollection $routes
 */

$routes->group(ADMIN_AREA, ['namespace' => '\Bonfire\Users\Controllers'], static function ($routes) {
    // User Settings
    $routes->get('settings/users', 'UserSettingsController::index', ['as' => 'user-settings']);
    $routes->post('settings/users', 'UserSettingsController::save');
    // Manage Users
    $routes->match(['get', 'post'], 'users', 'UserController::list', ['as' => 'user-list']);
    $routes->get('users/new', 'UserController::create', ['as' => 'user-new']);
    $routes->get('users/(:num)', 'UserController::edit/$1', ['as' => 'user-edit']);
    $routes->get('users/(:num)/delete', 'UserController::delete/$1', ['as' => 'user-delete']);
    $routes->post('users/(:num)/save', 'UserController::save/$1', ['as' => 'user-save']);
    $routes->post('users/(:num)/changePassword', 'UserController::changePassword/$1', ['as' => 'user-pass-change']);
    $routes->post('users/save', 'UserController::save');
    $routes->get('users/(:num)/security', 'UserController::security/$1');
    $routes->get('users/(:num)/permissions', 'UserController::permissions/$1');
    $routes->post('users/(:num)/permissions', 'UserController::savePermissions/$1');
});
