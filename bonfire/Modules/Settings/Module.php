<?php

namespace Bonfire\Modules\Settings;

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
            'title' => 'General',
            'namedRoute' => 'general-settings',
            'fontAwesomeIcon' => 'fas fa-gear',
        ]);
        $sidebar->menu('sidebar')->collection('settings')->addItem($item);
    }
}
