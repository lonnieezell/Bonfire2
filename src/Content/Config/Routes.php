<?php

use Bonfire\Content\Controllers\EntryController;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->group(ADMIN_AREA, ['namespace' => '\Bonfire\Content\Controllers'], static function ($routes) {
    $routes->get('taxonomies/(:segment)', 'TermAdminController::list/$1', ['as' => 'content-terms']);
    $routes->get('taxonomies/(:segment)/new', 'TermAdminController::create/$1', ['as' => 'content-term-new']);
    $routes->post('taxonomies/(:segment)/new', 'TermAdminController::store/$1');
    $routes->get('taxonomies/(:segment)/(:segment)', 'TermAdminController::edit/$1/$2', ['as' => 'content-term-edit']);
    $routes->post('taxonomies/(:segment)/(:segment)', 'TermAdminController::update/$1/$2');

    $routes->post('content/preview', 'EntryAdminController::preview', ['as' => 'content-preview']);
    $routes->get('content/(:segment)', 'EntryAdminController::list/$1', ['as' => 'content-list']);
    $routes->get('content/(:segment)/new', 'EntryAdminController::create/$1', ['as' => 'content-new']);
    $routes->post('content/(:segment)/new', 'EntryAdminController::store/$1');
    // Before the entry routes, which would take "structure" for an id.
    $routes->get('content/(:segment)/structure', 'EntryAdminController::structure/$1', ['as' => 'content-structure']);
    $routes->post('content/(:segment)/structure', 'EntryAdminController::saveStructure/$1');
    $routes->get('content/(:segment)/(:segment)', 'EntryAdminController::edit/$1/$2', ['as' => 'content-edit']);
    $routes->post('content/(:segment)/(:segment)', 'EntryAdminController::update/$1/$2');
});

if (config('Content')->autoRoute) {
    // This file loads last (see Bonfire\Core\Namespaces), and add() goes after the routes for a single verb.
    $routes->add('(:any)', [EntryController::class, 'show']);
}
