<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Widgets\Interfaces;

use Bonfire\Widgets\ItemSettings;

interface Item
{
    public function setTitle(?string $title): Item;

    public function settings(): ItemSettings;
}
