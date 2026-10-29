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

use Bonfire\Widgets\BaseWidget;
use Bonfire\Widgets\Types\Charts\Charts;
use Bonfire\Widgets\Types\Stats\Stats;

include_once __DIR__ . '/Common.php';

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
     */
    private array $moduleConfigs = [];

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
     *
     * @param bool $admin Boot for the admin area outside an admin request, like in a queue worker
     */
    public function boot(bool $admin = false)
    {
        helper('filesystem');

        $this->saveInAdmin();
        $this->inAdmin = $admin || $this->inAdmin;

        if ($this->inAdmin) {
            $this->setupMenus();
            $this->setupWidgets();
        }

        $this->discoverCoreModules();
        $this->discoverAppModules();
        $this->discoverPackageModules();
        $this->initModules();
    }

    /**
     * Checks to see if we're in the admin area
     * by checking whether the current path starts with the ADMIN_AREA constant.
     */
    private function saveInAdmin()
    {
        $path = uri_string();

        $this->inAdmin = $path === ADMIN_AREA || str_starts_with($path, ADMIN_AREA . '/');
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
            ->createCollection('content', lang('Bonfire.contentModTitle'));
        $menus->menu('sidebar')
            ->createCollection('settings', lang('Bonfire.settingsModTitle'))
            ->setFontAwesomeIcon('fas fa-cog')
            ->setCollapsible()
            ->setPageNav();
        $menus->menu('sidebar')
            ->createCollection('tools', lang('Bonfire.toolsModTitle'))
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

        $widgets->createWidget(BaseWidget::class, 'logs');
    }

    /**
     * Adds any directories within bonfire/Modules
     * into routes as a new namespace so that discover
     * features will automatically work for core modules.
     */
    private function discoverCoreModules()
    {
        if (! $modules = cache('bf-modules-search')) {
            $modules  = [];
            $excluded = ['Core', 'Config', 'Language', 'Views'];

            $map = directory_map(__DIR__, 1);

            foreach ($map as $row) {
                if (substr($row, -1) !== DIRECTORY_SEPARATOR) {
                    continue;
                }

                $name = trim($row, DIRECTORY_SEPARATOR);
                if (in_array($name, $excluded, true)) {
                    continue;
                }

                $modules["Bonfire\\{$name}"] = __DIR__ . "/{$name}";
            }

            cache()->save('bf-modules-search', $modules);
        }

        $this->loadModules($modules);
    }

    private function discoverAppModules()
    {
        // A location without modules yet caches [], so compare with null to skip rescanning it
        if (($modules = cache('app-modules-search')) === null) {
            $modules  = [];
            $excluded = ['Core', 'Config', 'Language', 'Views'];

            $paths = config('Bonfire')->appModules;

            if (! is_array($paths) || $paths === []) {
                log_message('debug', 'No app modules directories specified. Skipping.');

                return;
            }

            foreach ($paths as $namespace => $dir) {
                if (! is_dir($dir)) {
                    continue;
                }

                $dir = rtrim((string) $dir, DIRECTORY_SEPARATOR);
                $map = directory_map($dir, 1);

                foreach ($map as $row) {
                    if (substr($row, -1) !== DIRECTORY_SEPARATOR) {
                        continue;
                    }

                    $name = trim($row, DIRECTORY_SEPARATOR);
                    if (in_array($name, $excluded, true)) {
                        continue;
                    }

                    $modules["{$namespace}\\{$name}"] = "{$dir}/{$name}";
                }
            }

            cache()->save('app-modules-search', $modules);
        }

        $this->loadModules($modules);
    }

    /**
     * Loads the modules that Composer packages register,
     * each as its own namespace and directory.
     */
    private function discoverPackageModules()
    {
        $this->loadModules(config('Bonfire')->packageModules);
    }

    /**
     * Saves an instance of each module's config,
     * skipping directories without a Module.php.
     *
     * @param array<string, string> $modules namespace => directory
     */
    private function loadModules(array $modules)
    {
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
