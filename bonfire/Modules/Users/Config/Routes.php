<?php

namespace Bonfire\Email\Config;

$routes->group(ADMIN_AREA, ['namespace' => '\Bonfire\Modules\Users\Controllers'], function($routes) {
    // User Settings
    $routes->get('settings/users', 'UserSettingsController::index', ['as' => 'user-settings']);
    $routes->post('settings/users', 'UserSettingsController::save');
    // Manage Users
    $routes->get('users', 'UserController::list', ['as' => 'user-list']);
    $routes->get('users/new', 'UserController::create', ['as' => 'user-new']);
    $routes->get('users/(:num)', 'UserController::edit', ['as' => 'user-edit']);
    $routes->get('users/(:num)/delete', 'UserController::delete', ['as' => 'user-delete']);
    $routes->post('users', 'UserController::save', ['as' => 'user-save']);
});