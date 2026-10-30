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
 * Menu Class
 *
 * Represent list of Menu items and collection
 */
class Menu
{
    /**
     * Holds all Menu items or Menu collections that appear at
     * top level in this menu.
     *
     * @var list<(MenuCollection|MenuItem)>
     */
    protected array $items = [];

    /**
     * Returns all Menu items or Menu collections in the menu.
     *
     * @return list<(MenuCollection|MenuItem)>
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * Adds a new Menu item
     *
     * @param MenuItem $item Instance of MenuItem
     */
    public function addItem(MenuItem $item): self
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Creates a new collection with default values for
     * everything except the `name` and `title`, which are
     * required parameters.
     *
     * @param string $name  name or slug of the new Menu Collection
     * @param string $title Title of the new Menu Collection
     */
    public function createCollection(string $name, string $title): MenuCollection
    {
        $collection = new MenuCollection();
        $collection->setName($name)->setTitle($title);

        $this->items[] = $collection;

        return $collection;
    }

    /**
     * Creates a new collection, if one with $name doesn't exist,
     * and adds the items to the collection.
     *
     * @param string $name  name of Menu Collection
     * @param array  $items Array of Menu Item
     */
    public function collect(string $name, array $items): MenuCollection
    {
        /**
         *  Get Menu Collection
         *
         * @var MenuCollection|null $collection
         */
        $collection = $this->collection($name);

        if ($collection === null) {
            $collection = new MenuCollection();
            $collection->setName($name)->setTitle(ucfirst($name));

            $this->items[] = $collection;
        }

        $collection->addItems($items);

        return $collection;
    }

    /**
     * Locates a collection by name.
     *
     * @param string $name name of the Menu Collection
     *
     * @return MenuCollection|null
     */
    public function collection(string $name)
    {
        foreach ($this->items as $item) {
            if ($item instanceof MenuCollection && $item->name() === $name) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Returns an array of all collections stored, if any.
     *
     * @return list<MenuCollection>
     */
    public function collections(): array
    {
        $collections = [];

        foreach ($this->items as $item) {
            if ($item instanceof MenuCollection) {
                $collections[] = $item;
            }
        }

        return $collections;
    }

    /**
     * The visible item whose URL best (longest) matches the current URL, if any.
     */
    public function activeItem(): ?MenuItem
    {
        return $this->findActive()[1] ?? null;
    }

    /**
     * Titles leading to the current page: the collection title (for
     * collapsible collections) and the title of the active item.
     * Items outside a collection have only their own title.
     * Empty when no item matches.
     *
     * @return list<string>
     */
    public function activeTrail(): array
    {
        [$collection, $item] = $this->findActive() + [null, null];

        if ($item === null) {
            return [];
        }

        return $collection?->isCollapsible()
            ? [$collection->title, $item->title()]
            : [$item->title()];
    }

    /**
     * @return array{0: MenuCollection|null, 1: MenuItem}|array{}
     */
    private function findActive(): array
    {
        $active  = [];
        $longest = -1;

        foreach ($this->items as $entry) {
            $collection = $entry instanceof MenuCollection ? $entry : null;

            foreach ($collection?->items() ?? [$entry] as $item) {
                $url = rtrim($item->url(), '/');

                if (strlen($url) > $longest && url_is($url . '*') && $item->userCanSee()) {
                    $longest = strlen($url);
                    $active  = [$collection, $item];
                }
            }
        }

        return $active;
    }
}
