<?php

namespace Tests\Support\Packages\Courier;

use Bonfire\Core\BaseModule;

/**
 * Stands in for a Composer package's module, registered in Config\Bonfire::$packageModules.
 * Records which init method Bonfire called.
 */
class Module extends BaseModule
{
    public static array $calls = [];

    /**
     * Runs during initAdmin(), so tests can register menu items like a module would.
     *
     * @var (callable(): void)|null
     */
    public static $onInitAdmin;

    public function initAdmin()
    {
        self::$calls[] = 'initAdmin';

        if (self::$onInitAdmin !== null) {
            (self::$onInitAdmin)();
        }
    }

    public function initFront()
    {
        self::$calls[] = 'initFront';
    }
}
