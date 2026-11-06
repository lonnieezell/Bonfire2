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

use Bonfire\Fields\Field;
use Bonfire\Fields\FieldType;

/**
 * On or off, stored as true or false.
 */
final class ToggleField extends FieldType
{
    public function partial(): string
    {
        return 'toggle';
    }

    /**
     * A switch that's off posts nothing, so absent means off.
     */
    public function prepare(mixed $input, Field $field, array $submitted): string
    {
        return in_array(parent::prepare($input, $field, $submitted), ['1', 'on', 'true'], true) ? '1' : '0';
    }

    public function toStored(string $input, Field $field): bool
    {
        return $input === '1';
    }

    public function fromStored(mixed $stored, Field $field): string
    {
        return $stored === true || $stored === 1 || $stored === '1' || $stored === 'true' ? '1' : '0';
    }
}
