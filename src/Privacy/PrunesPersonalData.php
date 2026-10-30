<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Privacy;

/**
 * A PersonalDataProvider whose data expires. `spark bf:privacy:prune`
 * calls prune() on every registered provider that implements it.
 */
interface PrunesPersonalData
{
    /**
     * Deletes the data that is past its retention period.
     *
     * @return int How many records were deleted
     */
    public function prune(): int;
}
