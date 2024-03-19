<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Widgets\Types\Cards;

/**
 * Represents a collection of cards items.
 *
 * @property string $name
 * @property string $title
 */
class CardsCollection extends CardsItem
{
    /**
     * @var array
     */
    protected $items = [];

    /**
     * The name this collection is discovered by.
     *
     * @var string|null
     */
    protected $name;

    /**
     * @return $this
     */
    public function setName(string $name): CardsCollection
    {
        $this->name = $name;

        return $this;
    }

    public function name(): string
    {
        return $this->name ?? '';
    }

    /**
     * Adds a single item to the menu.
     *
     * @return $this
     */
    public function addItem(CardsItem $item): CardsCollection
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Add multiple items at once.
     *
     * @return $this
     */
    public function addItems(array $items): CardsCollection
    {
        $this->items = array_merge($this->items, $items);

        return $this;
    }

    /**
     * @return void
     */
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
    public function removeAllItems(): CardsCollection
    {
        $this->items = [];

        return $this;
    }

    /**
     * Returns all items in the Collection,
     * sorted by weight, where larger weights
     * make them fall to the bottom.
     */
    public function items(): array
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
