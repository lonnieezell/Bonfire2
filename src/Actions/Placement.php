<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Actions;

/**
 * Where an action is offered: in each row's menu, in the selection bar, or both.
 */
enum Placement
{
    case Row;
    case Bulk;
    case Both;

    public function inRow(): bool
    {
        return $this !== self::Bulk;
    }

    public function inBulk(): bool
    {
        return $this !== self::Row;
    }
}
