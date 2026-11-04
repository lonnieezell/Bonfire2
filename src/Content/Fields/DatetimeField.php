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
 * A date and time, `2026-10-27 09:30:00`. It's a `date` field that always has a time.
 */
final class DatetimeField extends FieldType
{
    public function partial(): string
    {
        return 'datetime';
    }

    public function prepare(mixed $input, Field $field, array $submitted): string
    {
        return DateField::normalize(parent::prepare($input, $field, $submitted), true);
    }

    public function rules(Field $field): array|string
    {
        return $this->withRules($field, 'permit_empty', 'valid_date[Y-m-d H:i:s]');
    }

    public function toFrontMatter(string $input, Field $field): ?string
    {
        return $input === '' ? null : $input;
    }

    public function fromFrontMatter(mixed $stored, Field $field): string
    {
        return is_string($stored) ? DateField::normalize($stored, true) : '';
    }
}
