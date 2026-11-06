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
 * A number with a fixed count of decimal places, stored as a string so that it never passes through a float.
 * `places` (default 2) is the most places it can have, `min` the lowest value, and `step` the input's step.
 */
final class DecimalField extends FieldType
{
    private const DEFAULT_PLACES = 2;
    private const NUMBER         = '/^(-?)(\d+)(?:\.(\d+))?$/';

    public function partial(): string
    {
        return 'decimal';
    }

    public function prepare(mixed $input, Field $field, array $submitted): string
    {
        return trim(parent::prepare($input, $field, $submitted));
    }

    public function rules(Field $field): array|string
    {
        $places = self::places($field);
        $min    = self::bound($field, 'min');

        return $this->withRules($field, 'permit_empty', static function (mixed $value, mixed $data, ?string &$error = null) use ($places, $min): bool {
            if (! is_string($value) || preg_match(self::NUMBER, $value, $parts) !== 1) {
                $error = lang('Fields.decimalInvalid');

                return false;
            }

            if (strlen($parts[3] ?? '') > $places) {
                $error = lang('Fields.decimalPlaces', [$places]);

                return false;
            }

            if ($min !== null && self::compare($value, $min) < 0) {
                $error = lang('Fields.decimalMin', [$min]);

                return false;
            }

            return true;
        });
    }

    public function toStored(string $input, Field $field): ?string
    {
        return $input === '' ? null : $input;
    }

    public function fromStored(mixed $stored, Field $field): string
    {
        $stored = is_int($stored) || is_float($stored) || is_string($stored) ? trim((string) $stored) : '';

        return preg_match(self::NUMBER, $stored) === 1 ? $stored : '';
    }

    /**
     * The most decimal places the field allows.
     */
    public static function places(Field $field): int
    {
        $places = $field->options['places'] ?? null;

        return is_int($places) && $places >= 0 ? $places : self::DEFAULT_PLACES;
    }

    /**
     * The field's `min`, as text, or null when it has none.
     */
    public static function bound(Field $field, string $key): ?string
    {
        $bound = $field->options[$key] ?? null;
        $bound = is_int($bound) || is_float($bound) || is_string($bound) ? trim((string) $bound) : '';

        return preg_match(self::NUMBER, $bound) === 1 ? $bound : null;
    }

    /**
     * The `step` of the input: the field's own, or else the smallest amount `places` allows.
     */
    public static function step(Field $field): string
    {
        $step = self::bound($field, 'step');

        if ($step !== null && self::compare($step, '0') > 0) {
            return $step;
        }

        $places = self::places($field);

        return $places === 0 ? '1' : '0.' . str_repeat('0', $places - 1) . '1';
    }

    /**
     * Compares two decimal numbers written as text, digit for digit, which a float can't do exactly.
     *
     * @return int -1, 0 or 1 as $a is lower than, equal to or higher than $b
     */
    private static function compare(string $a, string $b): int
    {
        preg_match(self::NUMBER, $a, $x);
        preg_match(self::NUMBER, $b, $y);

        $length  = max(strlen($x[3] ?? ''), strlen($y[3] ?? ''));
        $digitsA = ltrim($x[2] . str_pad($x[3] ?? '', $length, '0'), '0');
        $digitsB = ltrim($y[2] . str_pad($y[3] ?? '', $length, '0'), '0');

        // Zero has no sign.
        $signA = $digitsA === '' ? 0 : ($x[1] === '-' ? -1 : 1);
        $signB = $digitsB === '' ? 0 : ($y[1] === '-' ? -1 : 1);

        if ($signA !== $signB) {
            return $signA <=> $signB;
        }

        $order = strlen($digitsA) <=> strlen($digitsB) ?: strcmp($digitsA, $digitsB) <=> 0;

        return $signA < 0 ? -$order : $order;
    }
}
