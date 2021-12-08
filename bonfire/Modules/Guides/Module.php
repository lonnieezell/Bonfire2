<?php

namespace Bonfire\Modules\Guides;

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
            'title' => 'Guides',
            'namedRoute' => 'guides',
            'fontAwesomeIcon' => 'fas fa-book',
        ]);
        $sidebar->menu('sidebar')->collection('tools')->addItem($item);
    }
}
