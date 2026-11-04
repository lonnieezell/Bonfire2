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
use Closure;

/**
 * What a blueprint field type does. A form value is always a string, as the browser posted it.
 * The front matter value is what the file holds.
 */
abstract class FieldType
{
    /**
     * The name of the input partial, which renders in Bonfire\Content\Views\fields.
     */
    abstract public function partial(): string;

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
     * The CodeIgniter validation rules, which are the blueprint's `validate:` and whatever the type needs on top.
     *
     * @return list<Closure|string>|string
     */
    public function rules(Field $field): array|string
    {
        return $field->validate;
    }

    /**
     * The value to write to the file for a prepared, valid form value.
     */
    public function toFrontMatter(string $input, Field $field): mixed
    {
        return $input;
    }

    /**
     * The form value for what the file holds.
     */
    public function fromFrontMatter(mixed $stored, Field $field): string
    {
        return is_scalar($stored) ? (string) $stored : '';
    }

    /**
     * False for a type whose value is the file's name rather than a front matter key.
     */
    public function storedInFrontMatter(): bool
    {
        return true;
    }

    /**
     * The field's rules plus more, whether `validate:` was a string or a list. A rule that is a Closure
     * (value, data, &error) needs the list.
     *
     * @return list<Closure|string>|string
     */
    protected function withRules(Field $field, Closure|string ...$more): array|string
    {
        if (is_array($field->validate) || array_filter($more, is_object(...)) !== []) {
            $rules = is_array($field->validate) ? $field->validate : array_values(array_filter(explode('|', $field->validate), static fn (string $rule): bool => $rule !== ''));

            return [...$rules, ...$more];
        }

        return implode('|', array_filter([$field->validate, ...$more], static fn (string $rule): bool => $rule !== ''));
    }
}
