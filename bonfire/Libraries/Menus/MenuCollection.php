<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Libraries\Menus;

/**
 * Represents a collection of menu items.
 * This is used to store the dropdown button for dropdown menus,
 * or the header for accordion-style menus.
 *
 * @property string $name
 * @property string $title
 */
class MenuCollection extends MenuItem
{
    /**
     * @var array
     */
    protected $items = [];

    /**
     * The name this collection is discovered by.
     *
     * @var string
     */
    protected $name;

    /**
     * If true, should be presented as a collapsible menu.
     *
     * @var bool
     */
    protected $collapsible = false;

    /**
     * Sets the name this collection can be referenced by.
     *
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * @return $this
     */
    public function setCollapsible(bool $collapse = true): MenuCollection
    {
        $this->collapsible = $collapse;

        return $this;
    }

    public function isCollapsible(): bool
    {
        return $this->collapsible;
    }

    /**
     * Adds a single item to the menu.
     *
     * @return $this
     */
    public function addItem(MenuItem $item)
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Add multiple items at once.
     *
     * @return $this
     */
    public function addItems(array $items)
    {
        $this->items = array_merge($this->items, $items);

        return $this;
    }

    public function removeItem(string $title)
    {
        for ($i = 0; $i < count($this->items); $i++) {
            if ($this->items[$i]->title() === $title) {
                unset($this->items[$i]);
                break;
            }
        }
    }

    /**
     * Removes all of the items from this collection.
     *
     * @return $this
     */
    public function removeAllItems()
    {
        $this->items = [];

        return $this;
    }

    /**
     * Returns all items in the Collection,
     * sorted by weight, where larger weights
     * make them fall to the bottom.
     *
     * @return array
     */
    public function items()
    {
        $this->sortItems();

        return $this->items;
    }

    /**
     * Sorts the items by the weight,
     * ensuring that bigger weights
     * drop to the bottom.
     */
    protected function sortItems()
    {
        usort($this->items, static function ($a, $b) {
            if ($a->weight === $b->weight) {
                return $a->title <=> $b->title;
            }

            return $a->weight <=> $b->weight;
        });
    }

    public function __get(string $key)
    {
        if (method_exists($this, $key)) {
            return $this->{$key}();
        }
    }
}
