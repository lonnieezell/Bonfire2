<?php

namespace Bonfire\Modules\Users;

use Bonfire\Config\BaseModule;
use Bonfire\Libraries\Menus\MenuItem;
use Bonfire\Resources\ResourceTab;

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

        // Content Menu for sidebar
        $item = new MenuItem([
            'title' => 'Users',
            'namedRoute' => 'user-list',
            'fontAwesomeIcon' => 'fas fa-users'
        ]);
        $sidebar->menu('sidebar')->collection('content')->addItem($item);

        service('resourceTabs')->addTabFor('user', new ResourceTab([
                'title' => 'Google',
                'url' => 'https://google.com',
            ]));
    }
}

