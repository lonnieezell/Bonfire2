<?php

use Bonfire\Content\Controllers\EntryController;
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
if (config('Content')->autoRoute) {
    // This file loads last (see Bonfire\Core\Namespaces), and add() goes after the routes for a single verb.
    $routes->add('(:any)', [EntryController::class, 'show']);
}
