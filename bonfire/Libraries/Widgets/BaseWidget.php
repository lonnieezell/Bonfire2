<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Libraries\Widgets;

use Bonfire\Libraries\Widgets\Interfaces\Item;

class BaseWidget
{
    /**
     * @var array
     */
    protected $items = [];

    public function items(): array
    {
        return $this->items;
    }

    public function addItem(Item $item): BaseWidget
    {
        $this->items[] = $item;

        return $this;
    }
}
