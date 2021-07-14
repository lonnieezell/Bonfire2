<?php

// Bonfire Admin routes
$routes->group(ADMIN_AREA, ['namespace' => '\Bonfire\Controllers'], function($routes) {
	$routes->get('/', 'Dashboard::index');
});
