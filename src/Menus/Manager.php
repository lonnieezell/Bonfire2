<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Menus;

/**
 * Class Manager
 *
 * The main class used to work with menus in the system.
 */
class Manager
{
    /**
     * A collection of menus currently known about.
     *
     * @var array
     */
    private $menus = [];

    /**
     * Creates a new menu in the system.
     *
     * @return $this
     */
    public function createMenu(string $name)
    {
        $this->menus[$name] = new Menu();

        return $this;
    }

    /**
     * Returns the specified menu instance
     *
     * @return mixed
     */
    public function menu(string $name)
    {
        if (! isset($this->menus[$name])) {
            $this->createMenu($name);
        }

        return $this->menus[$name];
    }
}
