<?php

namespace Bonfire\Config;

use Bonfire\Bonfire;
use Bonfire\View\View;
use CodeIgniter\Config\BaseService;
use Config\Services as AppServices;
use Config\View as ViewConfig;
use Bonfire\Menus\Manager as MenuManager;
use Bonfire\Resources\ResourceTabs;
use Bonfire\Widgets\Manager as WidgetManager;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    /**
     * The Renderer class is the class that actually displays a file to the user.
     * The default View class within CodeIgniter is intentionally simple, but this
     * service could easily be replaced by a template engine if the user needed to.
     *
     * @return View
     */
    public static function renderer(?string $viewPath = null, ?ViewConfig $config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('renderer', $viewPath, $config);
        }

        $viewPath = $viewPath ?: config('Paths')->viewDirectory;
        $config ??= config('View');

        return new View($config, $viewPath, AppServices::locator(), CI_DEBUG, AppServices::logger());
    }

    /**
     * Core utility class for Bonfire's system.
     *
     * @return Bonfire|mixed
     */
    public static function bonfire(bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('bonfire');
        }

        return new Bonfire();
    }

    /**
     * Returns the system menu manager
     *
     * @return Bonfire\Menus\Manager|mixed
     */
    public static function menus(bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('menus');
        }

        return new MenuManager();
    }

    /**
     * Returns the Resource Tab manager that integrates
     * extra tabs into resources like Users, User Groups, etc.
     *
     * @return \Bonfire\Resources\ResourceTabs|mixed
     */
    public static function resourceTabs(bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('resourceTabs');
        }

        return new ResourceTabs();
    }

    /**
     * Returns the system widgets stats manager
     *
     * @return Bonfire\Libraries\Widgets\Manager|mixed
     */
    public static function widgets(bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('widgets');
        }

        return new WidgetManager();
    }
}
