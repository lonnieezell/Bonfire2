<?php

namespace Bonfire\Modules\Tools;

use Bonfire\Config\BaseModule;
use Bonfire\Libraries\Menus\MenuItem;

/**
 * Email Module setup
 *
 * @package Bonfire\Tools
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
            'title' => 'System Info',
            'namedRoute' => 'sys-info',
        ]);
        $sidebar->menu('sidebar')->collection('tools')->addItem($item);
    }
}
