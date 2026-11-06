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
 * A `select` field: one option of a list, or several.
 */
class Select extends Field
{
    protected const TYPE = 'select';

    /**
     * A list of values, or a map of value to label.
     *
     * @param array<int|string, string> $options
     */
    public function options(array $options): static
    {
        return $this->with(['options' => $options]);
    }

    public function multiple(bool $multiple = true): static
    {
        return $this->with(['multiple' => $multiple]);
    }
}
