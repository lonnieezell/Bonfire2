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

use Bonfire\Widgets\BaseWidget;
use Bonfire\Widgets\Interfaces\Widgets;

class Cards extends BaseWidget implements Widgets
{
    public function createCollection(string $name): CardsCollection
    {
        $collection = new CardsCollection();
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
            if ($item instanceof CardsCollection && $item->name() === $name) {
                return $item;
            }
        }
    }

    public function collect(string $name, array $items)
    {
        $collection = $this->collection($name);

        if ($collection === null) {
            $collection = new CardsCollection();
            $collection->setName($name)->setTitle(ucfirst($name));

            $this->items[] = $collection;
        }

        $collection->addItems($items);

        return $collection;
    }
}
