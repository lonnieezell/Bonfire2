<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Modules\Recycler;

use Bonfire\Config\BaseModule;
use Bonfire\Libraries\Menus\MenuItem;

/**
 * Docs Module setup
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
            'title'           => 'Recycler',
            'namedRoute'      => 'recycler',
            'fontAwesomeIcon' => 'fas fa-recycle',
            'permission'      => 'recycler.view',
        ]);
        $sidebar->menu('sidebar')->collection('tools')->addItem($item);
    }
}
