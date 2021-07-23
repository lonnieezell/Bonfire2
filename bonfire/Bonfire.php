<?php

namespace Bonfire;

use CodeIgniter\Config\Factories;

/**
 * Class Bonfire
 *
 * Provides basic utility functions used throughout the
 * lifecycle of a request in the admin area.
 *
 * @package Bonfire
 */
class Bonfire
{
    /**
     * Holds cached instances of all Module classes
     * @var array
     */
    private $moduleConfigs = [];

    /**
     * Are we currently in the admin area?
     * @var bool
     */
    public $inAdmin = false;

    /**
     * @var array
     */
    public $menus = [];

    /**
     * Sets up admin menus, initializes modules.
     */
    public function boot()
    {
        helper('filesystem');

        $this->saveInAdmin();

        if ($this->inAdmin) {
            $this->setupMenus();
        }

        $this->discoverCoreModules();
        $this->initModules();
    }

    /**
     * Checks to see if we're in the admin area
     * by analyzing the current url and the ADMIN_AREA constant.
     */
    private function saveInAdmin()
    {
        $url = current_url();

        $path = parse_url($url, PHP_URL_PATH);

        $this->inAdmin = strpos($path, ADMIN_AREA) !== false;
    }

    /**
     * Creates any admin-required menus so they're
     * available to use by any modules.
     */
    private function setupMenus()
    {
        $menus =  service('menus');

        // Sidebar menu
        $menus->createMenu('sidebar');
        $menus->menu('sidebar')
            ->createCollection('settings', 'Settings')
            ->setFontAwesomeIcon('fas fa-cog');

        // Top "icon" menu for notifications, account, etc.
        $menus->createMenu('iconbar');
    }

    /**
     * Adds any directories within bonfire/Modules
     * into routes as a new namespace so that discover
     * features will automatically work for core modules.
     */
    private function discoverCoreModules()
    {
        if (! $modules = cache('bf-modules-search')) {
            $modules = [];

            $map = directory_map(ROOTPATH . 'bonfire/Modules', 1);

            foreach ($map as $row) {
                if (substr($row, -1) !== '/') {
                    continue;
                }

                $name = trim($row, '/');
                $modules["Bonfire\\{$name}"] = ROOTPATH . "bonfire/Modules/{$name}";
            }

            cache()->save('bf-modules-search', $modules);
        }

        // save instances of our module configs
        foreach($modules as $namespace => $dir) {
            if (! is_file($dir .'/Module.php')) {
                continue;
            };

            include_once $dir .'/Module.php';
            $className = $namespace.'\Module';
            $this->moduleConfigs[$namespace] = new $className();
        }
    }

    /**
     * Performs any initialization needed for our modules.
     */
    private function initModules()
    {
        $method = $this->inAdmin ? 'initAdmin' : 'initFront';

        foreach ($this->moduleConfigs as $config) {
            $config->{$method}($this);
        }
    }
}
