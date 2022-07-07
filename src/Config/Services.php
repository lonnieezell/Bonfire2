<?php

namespace Bonfire\Config;

use Bonfire\Bonfire;
use CodeIgniter\Config\BaseService;
use Bonfire\Menus\Manager as MenuManager;
use Bonfire\Resources\ResourceTabs;
use Bonfire\Widgets\Manager as WidgetManager;
use Bonfire\View\Metadata;

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
     * Returns the view metadata manager.
     *
     * @return Bonfire\View\Metadata
     */
    public static function viewMeta(bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('viewMeta');
        }

        return new Metadata();
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
