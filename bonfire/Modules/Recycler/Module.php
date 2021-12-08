<?php

namespace Bonfire\Modules\Recycler;

use Bonfire\Config\BaseModule;
use Bonfire\Libraries\Menus\MenuItem;

/**
 * Docs Module setup
 *
 * @package Bonfire\Docs
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
            'title' => 'Recycler',
            'namedRoute' => 'recycler',
            'fontAwesomeIcon' => 'fas fa-recycle',
        ]);
        $sidebar->menu('sidebar')->collection('tools')->addItem($item);
    }
}
