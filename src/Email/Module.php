<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Email;

use Bonfire\Core\BaseModule;
use Bonfire\Menus\MenuItem;

/**
 * Email Module setup
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
            'title'           => lang('Email.emailModTitle'),
            'namedRoute'      => 'email-settings',
            'fontAwesomeIcon' => 'fas fa-envelope',
            'permission'      => 'admin.settings',
        ]);
        $sidebar->menu('sidebar')->collection('settings')->addItem($item);

        $sidebar->menu('sidebar')->collection('tools')->addItem(new MenuItem([
            'title'           => lang('Email.previewsModTitle'),
            'namedRoute'      => 'mail-previews',
            'fontAwesomeIcon' => 'fas fa-envelope-open-text',
            'permission'      => 'email.preview',
        ]));

        $sidebar->menu('sidebar')->collection('tools')->addItem(new MenuItem([
            'title'           => lang('Email.outboxModTitle'),
            'namedRoute'      => 'mail-outbox',
            'fontAwesomeIcon' => 'fas fa-paper-plane',
            'permission'      => 'email.log',
        ]));
    }
}
