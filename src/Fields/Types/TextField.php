<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Fields\Types;

use Bonfire\Fields\FieldType;

/**
 * A single line of text.
 */
final class TextField extends FieldType
{
    public function partial(): string
    {
        return 'text';
    }
}
