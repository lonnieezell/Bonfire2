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
 * A date and time, `2026-10-27 09:30:00`. It's a `date` field that always has a time, and does what that does.
 */
final class DatetimeField extends FieldType
{
    public function partial(): string
    {
        return 'datetime';
    }

    public function prepare(mixed $input, Field $field, array $submitted): string
    {
        return (new DateField())->prepare($input, self::asDate($field), $submitted);
    }

    public function rules(Field $field): array|string
    {
        return (new DateField())->rules(self::asDate($field));
    }

    public function toStored(string $input, Field $field): ?string
    {
        return (new DateField())->toStored($input, self::asDate($field));
    }

    public function fromStored(mixed $stored, Field $field): string
    {
        return (new DateField())->fromStored($stored, self::asDate($field));
    }

    /**
     * The same field, with the time that makes a `date` a date and time.
     */
    private static function asDate(Field $field): Field
    {
        return new Field($field->handle, 'date', $field->label, $field->validate, $field->instructions, [...$field->options, 'time' => true]);
    }
}
