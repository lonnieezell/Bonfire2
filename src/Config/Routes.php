<?php
/**
 * @var CodeIgniter\Router\RouteCollection $routes
 */
$routes->get('site-offline', static function () {
    helper('setting');

    // If it's not offline but they've refreshed the page
    // take to the site home page.
    if (! site_offline()) {
        return redirect()->to('/');
    }

    return view(config('Site')->siteOfflineView);
});
