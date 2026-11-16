<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->group(ADMIN_AREA, ['namespace' => '\Bonfire\Media\Controllers'], static function ($routes) {
    $routes->get('media', 'MediaAdminController::list', ['as' => 'media-list']);
    $routes->post('media/upload', 'MediaAdminController::upload', ['as' => 'media-upload']);
    $routes->post('media/(:segment)/delete', 'MediaAdminController::delete/$1', ['as' => 'media-delete']);
    $routes->get('media/(:segment)', 'MediaAdminController::edit/$1', ['as' => 'media-edit']);
    $routes->post('media/(:segment)', 'MediaAdminController::update/$1');
});
