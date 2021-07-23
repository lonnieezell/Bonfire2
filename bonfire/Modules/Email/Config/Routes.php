<?php

namespace Bonfire\Email\Config;

$routes->group(ADMIN_AREA, ['namespace' => '\Bonfire\Email\Controllers'], function($routes) {
    $routes->get('settings/email', 'Settings::index', ['as' => 'email-settings']);
});
