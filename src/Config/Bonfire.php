<?php

namespace Bonfire\Config;

use CodeIgniter\Config\BaseConfig;

class Bonfire extends BaseConfig
{
    public $views = [
        'filter_list' => 'Bonfire\Views\_filter_list',
    ];

    /**
     * --------------------------------------------------------------------------
     * App Module locations
     * --------------------------------------------------------------------------
     *
     * Any folders that contain modules can be configured here.
     * When Bonfire boots up it will automatitcally load any modules in these
     * folders. The entries MUST be the namespace as the key, and the location
     * as the value.
     *
     *   'MyStuff' => 'app/Modules',
     *
     *  You may leave the array empty if you do not wish to use module discovery.
     */
    public $appModules = [
        // 'App\Modules' => APPPATH . 'Modules',
    ];

    /**
     * --------------------------------------------------------------------------
     * Menu ordering
     * --------------------------------------------------------------------------
     *
     * $menuWeights property is an array of named routes as keys with weight
     * asigned to each named route as a value. If the module's named route
     * is not represented in this array, it's weight will default to 0 (highest
     * in the menu).
     *
     *   // Main content:
     *   'user-list'     => 1,
     *   'custom-list'    => 2,
     *
     *   // Settings submenu:
     *   'user-group-settings' => 0,
     *   // ... other items here
     *
     *   // Tools submenu:
     *   'recycler'      => 1,
     *   'sys-info'      => 2,
     *   'sys-logs'      => 3,
     *
     * It is used by MenuItem class to assign non-default weight to a menu.
     *
     * Menu Users and the menus belonging to custom modules can be arranged
     * this way, as well as the submenus of Tools and Settings. The placement
     * of menus Tools and Settings will not be affected by this setting.
     */
    public $menuWeights = [];

    /**
     * --------------------------------------------------------------------------
     * Breaking changes state file
     * --------------------------------------------------------------------------
     *
     * Where `spark notify:breaking-changes` remembers the newest breaking change
     * it has already reported, so that it does not report it twice.
     *
     * The file must live outside the package folder, because `composer update`
     * replaces everything under `vendor/`. When this is null the marker goes to
     * `writable/bonfire-breaking-changes.json`; point it elsewhere if your
     * server recreates `writable/` on every deploy.
     */
    public $breakingChangeStateFile;
}
