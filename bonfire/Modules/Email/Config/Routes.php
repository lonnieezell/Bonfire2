<?php

namespace Bonfire\Email\Config;

$routes->group(ADMIN_AREA, ['namespace' => '\Bonfire\Modules\Email\Controllers'], function ($routes) {
    $routes->get('settings/email', 'EmailSettingsController::index', ['as' => 'email-settings']);
    $routes->post('settings/email', 'EmailSettingsController::save');
    $routes->get('content/email', 'EmailContentController::index', ['as' => 'email-queue']);
    $routes->get('content/email_preview/(:num)', 'EmailContentController::preview/$1', ['as' => 'email-preview']);
    $routes->get('content/process_queue', 'EmailContentController::process_queue/$1', ['as' => 'process-queue']);
    $routes->post('content/delete-email', 'EmailContentController::delete', ['as' => 'email-delete']);

});
