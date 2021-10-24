<?php

$routes->group(ADMIN_AREA.'/settings', ['namespace' => '\Bonfire\Modules\Settings\Controllers'], function ($routes) {

    $routes->get('general', 'GeneralSettingsController::general', ['as' => 'general-settings']);
    $routes->post('general', 'GeneralSettingsController::saveGeneral');
});
