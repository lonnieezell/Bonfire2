<?php

use CodeIgniter\Router\RouteCollection;

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */
/** @var RouteCollection $routes */
$routes->group(ADMIN_AREA, ['namespace' => '\Bonfire\Email\Controllers'], static function ($routes) {
    $routes->get('settings/email', 'EmailSettingsController::index', ['as' => 'email-settings']);
    $routes->post('settings/email', 'EmailSettingsController::save');
    // CSRF is on here even if the app hasn't turned it on globally, because this sends mail.
    $routes->post('settings/email/test', 'EmailSettingsController::testSend', ['as' => 'email-test-send', 'filter' => 'csrf']);
    $routes->get('tools/mail/previews', 'MailPreviewsController::index', ['as' => 'mail-previews']);
    $routes->get('tools/mail/previews/(:segment)', 'MailPreviewsController::show/$1', ['as' => 'mail-preview']);
    // CSRF is on here even if the app hasn't turned it on globally, because this sends mail.
    $routes->post('tools/mail/previews/(:segment)/send', 'MailPreviewsController::send/$1', ['as' => 'mail-preview-send', 'filter' => 'csrf']);
});
