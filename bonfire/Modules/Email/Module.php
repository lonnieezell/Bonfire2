<?php

namespace Bonfire\Email;

use Bonfire\Config\BaseModule;
use Bonfire\Libraries\Menus\MenuItem;

/**
 * Email Module setup
 *
 * @package Bonfire\Email
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
            'title' => 'Email',
            'namedRoute' => 'email-settings',
        ]);
        $sidebar->menu('sidebar')->collection('settings')->addItem($item);
    }
}

