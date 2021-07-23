<?php

namespace Bonfire\Libraries\Menus;

/**
 * Class Manager
 *
 * The main class used to work with menus in the system.
 *
 * @package Bonfire\Libraries\Menus
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
     * @param string $name
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
     * @param string $name
     *
     * @return mixed
     */
    public function menu(string $name)
    {
        return $this->menus[$name];
    }
}
