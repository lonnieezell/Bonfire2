<?php

namespace Bonfire\Email\Config;

$routes->group(ADMIN_AREA, ['namespace' => '\Bonfire\Modules\Email\Controllers'], function ($routes) {
    $routes->get('settings/email', 'EmailSettingsController::index', ['as' => 'email-settings']);
    $routes->post('settings/email', 'EmailSettingsController::save');
});
