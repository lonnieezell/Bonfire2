<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire;

use Bonfire\Libraries\Widgets\Charts\Charts;
use Bonfire\Libraries\Widgets\Stats\Stats;

/**
 * Class Bonfire
 *
 * Provides basic utility functions used throughout the
 * lifecycle of a request in the admin area.
 */
class Bonfire
{
    /**
     * Holds cached instances of all Module classes
     *
     * @var array
     */
    private $moduleConfigs = [];

    /**
     * Are we currently in the admin area?
     *
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
            $this->setupWidgets();
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
        $menus = service('menus');

        // Sidebar menu
        $menus->createMenu('sidebar');
        $menus->menu('sidebar')
            ->createCollection('content', 'Content');
        $menus->menu('sidebar')
            ->createCollection('settings', 'Settings')
            ->setFontAwesomeIcon('fas fa-cog')
            ->setCollapsible();
        $menus->menu('sidebar')
            ->createCollection('tools', 'Tools')
            ->setFontAwesomeIcon('fas fa-toolbox')
            ->setCollapsible();

        // Top "icon" menu for notifications, account, etc.
        $menus->createMenu('iconbar');
    }

    /**
     * Creates any admin-required widgets so they're
     * available to use by any modules.
     */
    private function setupWidgets()
    {
        $widgets = service('widgets');

        $widgets->createWidget(Stats::class, 'stats');
        $widgets->widget('stats')
            ->createCollection('stats');

        $widgets->createWidget(Charts::class, 'charts');
        $widgets->widget('charts')
            ->createCollection('charts');
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
                if (substr($row, -1) !== DIRECTORY_SEPARATOR) {
                    continue;
                }

                $name                                 = trim($row, DIRECTORY_SEPARATOR);
                $modules["Bonfire\\Modules\\{$name}"] = ROOTPATH . "bonfire/Modules/{$name}";
            }

            cache()->save('bf-modules-search', $modules);
        }

        // save instances of our module configs
        foreach ($modules as $namespace => $dir) {
            if (! is_file($dir . '/Module.php')) {
                continue;
            }

            include_once $dir . '/Module.php';
            $className                       = $namespace . '\Module';
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
