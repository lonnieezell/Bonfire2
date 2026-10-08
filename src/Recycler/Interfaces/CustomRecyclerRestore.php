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
 * Implement on a model to replace the Recycler's default restore,
 * which clears the deleted field of the record.
 */
interface CustomRecyclerRestore
{
    /**
     * @return bool Whether the record was restored
     */
    public function recyclerRestore(int $id): bool;
}
