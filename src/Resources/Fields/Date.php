<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Resources\Fields;

/**
 * A `date` field, or with `time()` a date and time.
 */
class Date extends Field
{
    protected const TYPE = 'date';

    public function time(bool $time = true): static
    {
        return $this->with(['time' => $time]);
    }
}
