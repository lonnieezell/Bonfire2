<@php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->group(ADMIN_AREA, ['namespace' => '<?= $namespace ?>\Controllers'], static function ($routes) {
    $routes->get('<?= $slug ?>', '<?= $controller ?>::list', ['as' => '<?= $slug ?>-list']);
    $routes->get('<?= $slug ?>/new', '<?= $controller ?>::create', ['as' => '<?= $slug ?>-new']);
    $routes->post('<?= $slug ?>/save', '<?= $controller ?>::save', ['as' => '<?= $slug ?>-create']);
    $routes->get('<?= $slug ?>/(:num)', '<?= $controller ?>::edit/$1', ['as' => '<?= $slug ?>-edit']);
    $routes->post('<?= $slug ?>/(:num)/save', '<?= $controller ?>::save/$1', ['as' => '<?= $slug ?>-save']);
});
