<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Libraries\Widgets\Charts;

use Bonfire\Libraries\Widgets\BaseWidget;
use Bonfire\Libraries\Widgets\Interfaces\Widgets;

class Charts extends BaseWidget implements Widgets
{
    public function createCollection(string $name): ChartsCollection
    {
        $collection = new ChartsCollection();
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
            if ($item instanceof ChartsCollection && $item->name() === $name) {
                return $item;
            }
        }
    }

    public function collect(string $name, array $items)
    {
        $collection = $this->collection($name);

        if ($collection === null) {
            $collection = new ChartsCollection();
            $collection->setName($name)->setTitle(ucfirst($name));

            $this->items[] = $collection;
        }

        $collection->addItems($items);

        return $collection;
    }
}
