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
 * An `integer` field: a whole number.
 */
class Integer extends Field
{
    protected const TYPE = 'integer';

    public function min(int $min): static
    {
        return $this->with(['min' => $min]);
    }

    public function max(int $max): static
    {
        return $this->with(['max' => $max]);
    }
}
