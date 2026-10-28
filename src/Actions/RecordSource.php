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
 * Loads the records a list's actions work on.
 */
interface RecordSource
{
    /**
     * @param list<int|string> $ids
     *
     * @return list<object> The records that exist; missing IDs are left out
     */
    public function find(array $ids): array;
}
