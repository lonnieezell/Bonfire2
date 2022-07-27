<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Tools;

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
            'title'           => 'System Info',
            'namedRoute'      => 'sys-info',
            'fontAwesomeIcon' => 'fas fa-info-circle',
        ]);

        $itemLogs = new MenuItem([
            'title'           => 'Logs',
            'namedRoute'      => 'sys-logs',
            'fontAwesomeIcon' => 'fas fa-clipboard-list',
            'permission'      => 'logs.view',
        ]);
        $sidebar->menu('sidebar')->collection('tools')->addItem($item);
        $sidebar->menu('sidebar')->collection('tools')->addItem($itemLogs);
    }
}
