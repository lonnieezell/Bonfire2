<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Recycler\Interfaces;

/**
 * Implement on a model to replace the Recycler's default purge,
 * which permanently deletes the record through the model.
 */
interface CustomRecyclerPurge
{
    /**
     * @return bool Whether the record was purged
     */
    public function recyclerPurge(int $id): bool;
}
