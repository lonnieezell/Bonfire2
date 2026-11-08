<?php

use Bonfire\Content\Controllers\EntryController;
use Bonfire\Content\Controllers\FeedController;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->group(ADMIN_AREA, ['namespace' => '\Bonfire\Content\Controllers'], static function ($routes) {
    $routes->get('taxonomies/(:segment)', 'TermAdminController::list/$1', ['as' => 'content-terms']);
    $routes->get('taxonomies/(:segment)/new', 'TermAdminController::create/$1', ['as' => 'content-term-new']);
    $routes->post('taxonomies/(:segment)/new', 'TermAdminController::store/$1');
    $routes->get('taxonomies/(:segment)/(:segment)', 'TermAdminController::edit/$1/$2', ['as' => 'content-term-edit']);
    $routes->post('taxonomies/(:segment)/(:segment)', 'TermAdminController::update/$1/$2');

    $routes->get('navigation/(:segment)', 'NavigationAdminController::edit/$1', ['as' => 'content-navigation']);
    $routes->post('navigation/(:segment)', 'NavigationAdminController::save/$1');

    // Not under globals/, because GlobalsTest checks that globals have no route of their own on the front end.
    $routes->get('site-values/(:segment)', 'GlobalAdminController::edit/$1', ['as' => 'content-global']);
    $routes->post('site-values/(:segment)', 'GlobalAdminController::save/$1');

    $routes->get('content-block/(:segment)/(:segment)', 'EntryAdminController::block/$1/$2', ['as' => 'content-block']);
    $routes->post('content/preview', 'EntryAdminController::preview', ['as' => 'content-preview']);
    $routes->get('content/(:segment)', 'EntryAdminController::list/$1', ['as' => 'content-list']);
    $routes->get('content/(:segment)/new', 'EntryAdminController::create/$1', ['as' => 'content-new']);
    $routes->post('content/(:segment)/new', 'EntryAdminController::store/$1');
    // Before the entry routes, which would take "structure" for an id.
    $routes->get('content/(:segment)/structure', 'EntryAdminController::structure/$1', ['as' => 'content-structure']);
    $routes->post('content/(:segment)/structure', 'EntryAdminController::saveStructure/$1');
    $routes->get('content/(:segment)/(:segment)/preview', 'EntryAdminController::previewLink/$1/$2', ['as' => 'content-preview-link']);
    $routes->post('content/(:segment)/(:segment)/delete', 'EntryAdminController::delete/$1/$2', ['as' => 'content-delete']);
    $routes->get('content/(:segment)/(:segment)', 'EntryAdminController::edit/$1/$2', ['as' => 'content-edit']);
    $routes->post('content/(:segment)/(:segment)', 'EntryAdminController::update/$1/$2');
});

if (config('Content')->sitemap) {
    $routes->get('sitemap.xml', [EntryController::class, 'sitemap']);
}

// Outside the admin, so it needs no login, only a link that Bonfire signed. Before the fallback route below.
$routes->get('content-preview/(:segment)', [[EntryController::class, 'preview'], '$1'], ['as' => 'content-draft-preview']);

if (config('Content')->feedRoute) {
    // Before the fallback route below, which would take the URL. A route of the app's with the same pattern is kept.
    $routes->get('feed/(:segment)', [FeedController::class, 'show']);
}

if (config('Content')->autoRoute) {
    // This file loads last (see Bonfire\Core\Namespaces), and add() goes after the routes for a single verb.
    $routes->add('(:any)', [EntryController::class, 'show']);
}
