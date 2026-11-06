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
 * A date, `2026-10-27`, or with `time: true` a date and time, `2026-10-27 09:30:00`.
 */
final class DateField extends FieldType
{
    public function partial(): string
    {
        return 'date';
    }

    public function prepare(mixed $input, Field $field, array $submitted): string
    {
        return self::normalize(parent::prepare($input, $field, $submitted), self::hasTime($field));
    }

    public function rules(Field $field): array|string
    {
        return $this->withRules($field, 'permit_empty', 'valid_date[' . (self::hasTime($field) ? 'Y-m-d H:i:s' : 'Y-m-d') . ']');
    }

    public function toStored(string $input, Field $field): ?string
    {
        return $input === '' ? null : $input;
    }

    public function fromStored(mixed $stored, Field $field): string
    {
        return is_string($stored) ? self::normalize($stored, self::hasTime($field)) : '';
    }

    public static function hasTime(Field $field): bool
    {
        return ($field->options['time'] ?? false) === true;
    }

    /**
     * Puts a date a browser or a file holds in one form: 2026-10-27, or 2026-10-27 09:30:00 with a time.
     * Anything else comes back as it was, for validation to refuse.
     */
    public static function normalize(string $input, bool $withTime): string
    {
        if (preg_match('/^(\d{4}-\d{2}-\d{2})(?:[T ](\d{2}:\d{2})(?::(\d{2}))?)?$/', trim($input), $parts) !== 1) {
            return trim($input);
        }

        return $withTime ? "{$parts[1]} " . ($parts[2] ?? '00:00') . ':' . ($parts[3] ?? '00') : $parts[1];
    }
}
