<?php

namespace Bonfire\Guides\Config;

$routes->group(ADMIN_AREA, ['namespace' => '\Bonfire\Guides\Controllers'], function ($routes) {
    $routes->get('guides', 'GuidesController::viewCollections', ['as' => 'guides']);
    $routes->get('guides/(:segment)', 'GuidesController::viewCollection/$1', ['as' => 'view-guide-collection']);
    $routes->get('guides/(:segment)/(:any)', 'GuidesController::viewSingle/$1/$2', ['as' => 'view-guide']);
});

