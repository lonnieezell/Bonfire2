<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Modules\Tools;

use Bonfire\Config\BaseModule;
use Bonfire\Libraries\Menus\MenuItem;

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
            'title'      => 'System Info',
            'namedRoute' => 'sys-info',
        ]);

        $itemLogs = new MenuItem([
            'title'           => 'Logs',
            'namedRoute'      => 'sys-logs',
            'fontAwesomeIcon' => 'fas fa-clipboard-list',
        ]);
        $sidebar->menu('sidebar')->collection('tools')->addItem($item);
        $sidebar->menu('sidebar')->collection('tools')->addItem($itemLogs);
    }
}
