<?php

namespace Bonfire\Email\Config;

$routes->group(ADMIN_AREA, ['namespace' => '\Bonfire\Modules\Users\Controllers'], function($routes) {
    $routes->get('settings/users', 'UserSettingsController::index', ['as' => 'user-settings']);
    $routes->post('settings/users', 'UserSettingsController::save');
});
