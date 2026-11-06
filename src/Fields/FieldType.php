<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Fields;

use Closure;

/**
 * What a field type does. A form value is always a string, as the browser posted it.
 * The stored value is what the field's storage holds: a model's column, or a front matter key.
 */
abstract class FieldType
{
    /**
     * The name of the input partial. It renders from the view namespace the type gives, unless
     * the app has app/Views/fields/<partial>.php.
     */
    abstract public function partial(): string;

    /**
     * The namespace of the views that hold the type's partial.
     */
    public function viewNamespace(): string
    {
        return 'Bonfire\Fields\Views\fields';
    }

    /**
     * Tidies what was posted, before it's validated. Anything that isn't text counts as empty.
     *
     * @param array<array-key, mixed> $submitted every posted field, for a type that fills itself from another
     */
    public function prepare(mixed $input, Field $field, array $submitted): string
    {
        return is_string($input) ? $input : '';
    }

    /**
     * The CodeIgniter validation rules, which are the field's `validate:` and whatever the type needs on top.
     *
     * @return list<Closure|string>|string
     */
    public function rules(Field $field): array|string
    {
        return $field->validate;
    }

    /**
     * The PHP value to store for a prepared, valid form value.
     */
    public function toStored(string $input, Field $field): mixed
    {
        return $input;
    }

    /**
     * The form value for what the storage holds.
     */
    public function fromStored(mixed $stored, Field $field): string
    {
        return is_scalar($stored) ? (string) $stored : '';
    }

    /**
     * Checks the options the field was given.
     *
     * @throws FieldException when the field can't be used
     */
    public function check(Field $field): void
    {
    }

    /**
     * The field's rules plus more, whether `validate:` was a string or a list. A rule that is a Closure
     * (value, data, &error) needs the list.
     *
     * @return list<Closure|string>|string
     */
    protected function withRules(Field $field, Closure|string ...$more): array|string
    {
        $needsList = is_array($field->validate) || array_filter($more, is_object(...)) !== [];

        if ($needsList) {
            return [...(is_array($field->validate) ? $field->validate : self::splitRules($field->validate)), ...$more];
        }

        return implode('|', array_filter([$field->validate, ...$more], static fn (string $rule): bool => $rule !== ''));
    }

    /**
     * A `a|b[x|y]` string as its rules. A pipe inside brackets belongs to the rule.
     *
     * @return list<string>
     */
    private static function splitRules(string $rules): array
    {
        return array_values(array_filter(preg_split('/\|(?![^\[]*\])/', $rules) ?: [], static fn (string $rule): bool => $rule !== ''));
    }
}
