<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Libraries\Widgets\Interfaces;

interface Widgets
{
    /**
     * Adds a new item
     */
    public function addItem(Item $item): self;
}
