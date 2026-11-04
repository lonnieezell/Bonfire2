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
 * A whole number, kept in the front matter as a number. `min` and `max` bound it.
 */
final class IntegerField extends FieldType
{
    public function partial(): string
    {
        return 'integer';
    }

    /**
     * A number written as `+5` or ` 5 ` becomes `5`. Anything else is left for validation to refuse.
     */
    public function prepare(mixed $input, Field $field, array $submitted): string
    {
        $input = trim(parent::prepare($input, $field, $submitted));

        $number = filter_var($input, FILTER_VALIDATE_INT);

        return $number === false ? $input : (string) $number;
    }

    public function rules(Field $field): array|string
    {
        $min = self::bound($field, 'min');
        $max = self::bound($field, 'max');

        return $this->withRules($field, 'permit_empty', static function (mixed $value, mixed $data, ?string &$error = null) use ($min, $max): bool {
            $number = filter_var($value, FILTER_VALIDATE_INT);

            if ($number === false) {
                $error = lang('Content.integerInvalid');

                return false;
            }

            if ($min !== null && $number < $min) {
                $error = lang('Content.integerMin', [$min]);

                return false;
            }

            if ($max !== null && $number > $max) {
                $error = lang('Content.integerMax', [$max]);

                return false;
            }

            return true;
        });
    }

    public function toFrontMatter(string $input, Field $field): ?int
    {
        return $input === '' ? null : (int) $input;
    }

    public function fromFrontMatter(mixed $stored, Field $field): string
    {
        if (is_float($stored) && floor($stored) === $stored) {
            $stored = (int) $stored;
        }

        return is_int($stored) || (is_string($stored) && filter_var($stored, FILTER_VALIDATE_INT) !== false) ? (string) $stored : '';
    }

    /**
     * The blueprint's `min` or `max`, or null when it has none.
     */
    public static function bound(Field $field, string $key): ?int
    {
        return is_int($field->options[$key] ?? null) ? $field->options[$key] : null;
    }
}
