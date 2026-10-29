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

    public function initAdmin()
    {
        self::$calls[] = 'initAdmin';
    }

    public function initFront()
    {
        self::$calls[] = 'initFront';
    }
}
