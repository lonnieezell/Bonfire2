<?php
/**
 * @var CodeIgniter\Router\RouteCollection $routes
 */

// Authentication Routes that override Shield's
$routes->group('', ['namespace' => '\Bonfire\Auth\Controllers'], static function ($routes) {
    $routes->get('register', 'RegisterController::registerView');
    $routes->post('register', 'RegisterController::registerAction');
    $routes->get('login', 'LoginController::loginView');
    $routes->post('login', 'LoginController::loginAction');
    $routes->get('login/magic-link', 'MagicLinkController::loginView', ['as' => 'magic-link']);
    $routes->post('login/magic-link', 'MagicLinkController::loginAction');
    $routes->get('login/verify-magic-link', 'MagicLinkController::verify', ['as' => 'verify-magic-link']);
});

$routes->group('', ['namespace' => '\CodeIgniter\Shield\Controllers'], static function ($routes) {
    $routes->get('auth/a/show', 'ActionController::show', ['as' => 'auth-action-show']);
});

service('auth')->routes($routes, ['except' => ['login', 'register']]);
