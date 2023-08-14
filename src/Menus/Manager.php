<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * php version 8.0
 *
 * @category Menus
 * @license  MIT https://opensource.org/licenses/MIT
 * @see      https://github.com/lonnieezell/Bonfire2/
 */

namespace Bonfire\Menus;

/**
 * Menus Manager Class
 *
 * The main class used to work with menus in the system.
 *
 * @method self                createMenu(string $name)
 * @method \Bonfire\Menus\Menu menu(string $name)
 */
class Manager
{
    /**
     * A collection of menus currently known about.
     *
     * @var array<\Bonfire\Menus\Menu> array of `\Bonfire\Menus\Menu`
     */
    private array $_menus = [];

    /**
     * Creates a new menu in the system.
     *
     * @param string $name New Menu's name
     */
    public function createMenu(string $name): self
    {
        $this->_menus[$name] = new Menu();

        return $this;
    }

    /**
     * Returns the specified menu instance
     *
     * @param string $name Menu's name
     *
     * @return \Bonfire\Menus\Menu
     */
    public function menu(string $name): Menu
    {
        if (! isset($this->_menus[$name])) {
            $this->createMenu($name);
        }

        return $this->_menus[$name];
    }
}
