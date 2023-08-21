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
 * Represents a collection of menu items.
 * This is used to store the dropdown button for dropdown menus,
 * or the header for accordion-style menus.
 *
 * @property string $name
 * @property string $title
 */
class MenuCollection extends MenuItem
{
    use HasMenuIcons;

    /**
     * Holds all Menu items of a collection
     *
     * @var array<\Bonfire\Menus\MenuItem>
     */
    protected array $items = [];

    /**
     * The name this collection is discovered by.
     */
    protected string $name = '';

    /**
     * If true, should be presented as a collapsible menu.
     */
    protected bool $collapsible = false;

    /**
     * Sets the name this collection can be referenced by.
     *
     * @param string $name Name of Menu item
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets the name this collection can be referenced by.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Sets this collection is collapsible.
     *
     * @param bool $collapse Is collapsible or not
     */
    public function setCollapsible(bool $collapse = true): self
    {
        $this->collapsible = $collapse;

        return $this;
    }

    /**
     * Gets this collection is collapsible
     */
    public function isCollapsible(): bool
    {
        return $this->collapsible;
    }

    /**
     * Adds a single item to the menu.
     *
     * @param \Bonfire\Menus\MenuItem $item Instance of MenuItem
     */
    public function addItem(MenuItem $item): self
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Add multiple Menu items at once.
     *
     * @param array<\Bonfire\Menus\MenuItem> $items list of MenuItem Instance
     */
    public function addItems(array $items): self
    {
        $this->items = array_merge($this->items, $items);

        return $this;
    }

    /**
     * Remove Menu Item from this collection
     *
     * @param string $title title of MenuItem that want to remove
     */
    public function removeItem(string $title): void
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
     */
    public function removeAllItems(): self
    {
        $this->items = [];

        return $this;
    }

    /**
     * Returns all items in the Collection, sorted by weight,
     * where larger weights make them fall to the bottom.
     *
     * @return array<\Bonfire\Menus\MenuItem>
     */
    public function items(): array
    {
        $this->sortItems();

        return $this->items;
    }

    /**
     * Is this collection "active"? Used in default admin
     * theme to determine if the sub-navs should be open
     * or flyout.
     */
    public function isActive(): bool
    {
        $url = ADMIN_AREA . '/' . $this->name;

        return url_is($url . '*');
    }

    /**
     * Sorts the items by the weight, ensuring that bigger
     * weights drop to the bottom.
     */
    protected function sortItems(): void
    {
        usort(
            $this->items,
            static function ($a, $b) {
                if ($a->weight === $b->weight) {
                    return $a->title <=> $b->title;
                }

                return $a->weight <=> $b->weight;
            }
        );
    }

    /**
     * Returns true if the collection contains at least one item that is
     * visible to current user, false otherwise
     */
    public function hasVisibleItems(): bool
    {
        $visible = false;

        foreach ($this->items as $item) {
            if ($item->userCanSee()) {
                $visible = true;
                break;
            }
        }

        return $visible;
    }

    /**
     * Gets callable of MenuCollection Class
     *
     * @param string $key one of the Method name of MenuCollection Class
     *
     * @return mixed
     */
    public function __get(string $key)
    {
        if (method_exists($this, $key)) {
            return $this->{$key}();
        }
    }
}
