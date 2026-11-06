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
 * A `decimal` field: a number with a fixed count of places, stored as a string.
 */
class Decimal extends Field
{
    protected const TYPE = 'decimal';

    public function places(int $places): static
    {
        return $this->with(['places' => $places]);
    }

    public function min(float|int|string $min): static
    {
        return $this->with(['min' => $min]);
    }

    public function step(float|int|string $step): static
    {
        return $this->with(['step' => $step]);
    }
}
