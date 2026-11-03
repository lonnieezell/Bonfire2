<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Content\Fields;

use Bonfire\Content\Field;

/**
 * On or off, kept in the front matter as true or false.
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

    public function toFrontMatter(string $input, Field $field): bool
    {
        return $input === '1';
    }

    public function fromFrontMatter(mixed $stored, Field $field): string
    {
        return $stored === true || $stored === 1 || $stored === '1' || $stored === 'true' ? '1' : '0';
    }
}
