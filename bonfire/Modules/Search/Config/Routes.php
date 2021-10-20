<?php

namespace Bonfire\Search\Config;

$routes->group(ADMIN_AREA, ['namespace' => '\Bonfire\Modules\Search\Controllers'], function ($routes) {
    $routes->post('search', 'SearchController::overview', ['as' => 'search']);
});
