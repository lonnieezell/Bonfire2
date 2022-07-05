<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Users;

use Bonfire\Core\BaseModule;
use Bonfire\Menus\MenuItem;

class Module extends BaseModule
{
    /**
     * Setup our admin area needs.
     */
    public function initAdmin()
    {
        // Add to the Content menu
        $sidebar = service('menus');
        $item    = new MenuItem([
            'title'           => 'Users',
            'namedRoute'      => 'user-list',
            'fontAwesomeIcon' => 'fas fa-info-circle',
        ]);
        $sidebar->menu('sidebar')->collection('content')->addItem($item);
    }
}

