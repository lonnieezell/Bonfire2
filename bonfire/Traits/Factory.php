<?php

namespace Bonfire\Traits;

trait Factory
{
    private static $instance;

    /**
     * Factory method to get a shared instance of this class.
     *
     * @return self
     */
    public static function instance()
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        self::$instance = new self();

        return self::$instance;
    }
}
