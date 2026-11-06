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

use Bonfire\Fields\ChoiceList;
use Bonfire\Fields\Field;
use Bonfire\Fields\FieldType;

/**
 * One option of a list, or with `multiple: true` several, from the field's `options`. The options are
 * a list of values, or a map of value to label. Stored as a string, or as a list of them.
 */
final class SelectField extends FieldType
{
    public function partial(): string
    {
        return 'select';
    }

    public function prepare(mixed $input, Field $field, array $submitted): string
    {
        return self::isMultiple($field) ? ChoiceList::encode(ChoiceList::fromInput($input)) : trim(parent::prepare($input, $field, $submitted));
    }

    public function rules(Field $field): array|string
    {
        // PHP makes a key such as "1" an int, and a posted value is always a string.
        $allowed  = array_map(strval(...), array_keys(self::choices($field)));
        $multiple = self::isMultiple($field);

        return $this->withRules($field, 'permit_empty', static function (mixed $value, mixed $data, ?string &$error = null) use ($allowed, $multiple): bool {
            $value = is_string($value) ? $value : '';

            foreach ($multiple ? ChoiceList::decode($value) : [$value] as $chosen) {
                if (! in_array($chosen, $allowed, true)) {
                    $error = lang('Fields.selectInvalid');

                    return false;
                }
            }

            return true;
        });
    }

    public function toStored(string $input, Field $field): mixed
    {
        return self::isMultiple($field) ? (ChoiceList::decode($input) ?: null) : $input;
    }

    public function fromStored(mixed $stored, Field $field): string
    {
        return self::isMultiple($field) ? ChoiceList::encode(ChoiceList::fromStored($stored)) : parent::fromStored($stored, $field);
    }

    public static function isMultiple(Field $field): bool
    {
        return ($field->options['multiple'] ?? false) === true;
    }

    /**
     * The label of each value the field accepts.
     *
     * @return array<string, string>
     */
    public static function choices(Field $field): array
    {
        $options = $field->options['options'] ?? [];

        if (! is_array($options)) {
            return [];
        }

        // A list names the values, and a map names their labels.
        $isList  = array_is_list($options);
        $choices = [];

        foreach ($options as $value => $label) {
            if (is_scalar($label)) {
                $choices[(string) ($isList ? $label : $value)] = (string) $label;
            }
        }

        return $choices;
    }
}
