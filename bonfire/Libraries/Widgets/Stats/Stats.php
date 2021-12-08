<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Libraries\Widgets\Stats;

use Bonfire\Libraries\Widgets\Interfaces\Item;
use Bonfire\Libraries\Widgets\Interfaces\Widgets;

class Stats implements Widgets
{
    /**
     * @var array
     */
    protected $items = [];

    public function items()
    {
        return $this->items;
    }

    public function addItem(Item $item): Stats
    {
        $this->items[] = $item;

        return $this;
    }

    public function createCollection(string $name): StatsCollection
    {
        $collection = new StatsCollection();
        $collection->setName($name);

        $this->items[] = $collection;

        return $collection;
    }

    /**
     * Locates a collection by name.
     *
     * @return mixed
     */
    public function collection(string $name)
    {
        foreach ($this->items as $item) {
            if ($item instanceof StatsCollection && $item->name() === $name) {
                return $item;
            }
        }
    }

    public function collect(string $name, array $items)
    {
        $collection = $this->collection($name);

        if ($collection === null) {
            $collection = new StatsCollection();
            $collection->setName($name)->setTitle(ucfirst($name));

            $this->items[] = $collection;
        }

        $collection->addItems($items);

        return $collection;
    }
}
