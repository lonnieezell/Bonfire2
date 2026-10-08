<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Widgets;

use Bonfire\Widgets\Interfaces\Item;
use Bonfire\Widgets\Interfaces\Widgets;

/**
 * Class Manager
 *
 * The main class used to work with widgets in the system.
 */
class Manager
{
    /**
     * A collection of widgets currently known about.
     */
    public array $widgets = [];

    /**
     * Creates a new widget in the system.
     *
     * @param Widgets $widget
     */
    public function createWidget($widget, string $name): Manager
    {
        $this->widgets[$name] = new $widget();

        return $this;
    }

    /**
     * Returns the specified widget instance
     *
     * @return mixed
     */
    public function widget(string $name)
    {
        return $this->widgets[$name];
    }

    /**
     * Every item of every collection of every widget.
     *
     * @return list<Item>
     */
    public function items(): array
    {
        $items = [];

        foreach ($this->widgets as $widget) {
            foreach ($widget->items() as $collection) {
                array_push($items, ...$collection->items());
            }
        }

        return $items;
    }
}
