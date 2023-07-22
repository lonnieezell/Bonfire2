<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Core;

/**
 * Class BaseModule
 *
 * Provides a foundation for module configuration files.
 */
class BaseModule
{
    /**
     * During system boot for the admin area, this method
     * is called on all module config classes, allowing them
     * to perform any setup necessary, like hooking into the
     * sidebar menu, etc.
     */
    public function initAdmin()
    {
    }

    /**
     * During system boot for the front end, this method
     * is called on all module config classes, allowing them
     * to perform any setup necessary.
     */
    public function initFront()
    {
    }
}
