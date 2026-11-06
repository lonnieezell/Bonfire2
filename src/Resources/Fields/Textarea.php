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
 * A `textarea` field: several lines.
 */
class Textarea extends Field
{
    protected const TYPE = 'textarea';

    /**
     * The height of the input, in lines.
     */
    public function rows(int $rows): static
    {
        return $this->with(['rows' => $rows]);
    }
}
