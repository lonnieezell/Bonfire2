<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Consent;

use Bonfire\Core\BaseModule;
use Bonfire\Menus\MenuItem;

/**
 * Consent Module setup
 */
class Module extends BaseModule
{
    /**
     * Setup our admin area needs.
     */
    public function initAdmin()
    {
        // Settings menu for sidebar
        $sidebar = service('menus');
        $item    = new MenuItem([
            'title'           => lang('Consent.consentModTitle'),
            'namedRoute'      => 'consent-settings',
            'fontAwesomeIcon' => 'fas fa-handshake',
            'permission'      => 'consent.settings',
        ]);
        $sidebar->menu('sidebar')->collection('settings')->addItem($item);
    }
}
