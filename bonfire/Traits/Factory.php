<?php

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

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
