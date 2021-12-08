<?php

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Email\Config;

$routes->group(ADMIN_AREA, ['namespace' => '\Bonfire\Modules\Email\Controllers'], static function ($routes) {
    $routes->get('settings/email', 'EmailSettingsController::index', ['as' => 'email-settings']);
    $routes->post('settings/email', 'EmailSettingsController::save');
});
