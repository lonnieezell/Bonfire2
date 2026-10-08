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

use CodeIgniter\Model;

/**
 * Implement on a model to change which deleted records the Recycler lists,
 * e.g. to select extra columns. Return the model to run the query on.
 */
interface CustomRecyclerQuery
{
    public function setupRecycler(): Model;
}
