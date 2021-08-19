<?php

namespace Bonfire\Modules\Users;

use Bonfire\Config\BaseModule;
use Bonfire\Libraries\Menus\MenuItem;

/**
 * User Module setup
 *
 * @package Bonfire\User
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
        $item = new MenuItem([
            'title' => 'Users',
            'namedRoute' => 'user-settings',
            'fontAwesomeIcon' => 'fas fa-user',
        ]);
        $sidebar->menu('sidebar')->collection('settings')->addItem($item);
    }
}

