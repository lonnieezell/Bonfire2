<?php

// General "Site Offline" route
$routes->get('site-offline', function() {
    echo view('errors/html/offline.php');
});

// Bonfire Admin routes
$routes->group(ADMIN_AREA, ['namespace' => '\Bonfire\Controllers'], function ($routes) {
    $routes->get('/', 'Dashboard::index');
});
