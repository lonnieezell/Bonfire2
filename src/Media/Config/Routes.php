<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->group(ADMIN_AREA, ['namespace' => '\Bonfire\Media\Controllers'], static function ($routes) {
    $routes->get('media', 'MediaAdminController::list', ['as' => 'media-list']);
    $routes->get('media/picker', 'MediaAdminController::picker', ['as' => 'media-picker']);
    $routes->post('media/upload', 'MediaAdminController::upload', ['as' => 'media-upload', 'filter' => 'csrf']);
    $routes->post('media/(:segment)/delete', 'MediaAdminController::delete/$1', ['as' => 'media-delete', 'filter' => 'csrf']);
    $routes->get('media/(:segment)', 'MediaAdminController::edit/$1', ['as' => 'media-edit']);
    $routes->post('media/(:segment)', 'MediaAdminController::update/$1', ['filter' => 'csrf']);
});
