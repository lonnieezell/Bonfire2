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

use Bonfire\Content\ContentException;
use Bonfire\Content\Field;

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
     * @return list<string>|string
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
     * The front matter key that holds the field's value, which is the field's handle unless the type says otherwise.
     */
    public function key(Field $field): string
    {
        return $field->handle;
    }

    /**
     * Checks the options the blueprint gave the field.
     *
     * @throws ContentException when the field can't be used
     */
    public function check(Field $field): void
    {
    }

    /**
     * False for a type whose value is the file's name rather than a front matter key.
     */
    public function storedInFrontMatter(): bool
    {
        return true;
    }

    /**
     * The field's rules plus more, whether `validate:` was a string or a list.
     *
     * @return list<string>|string
     */
    protected function withRules(Field $field, string ...$more): array|string
    {
        if (is_array($field->validate)) {
            return [...$field->validate, ...$more];
        }

        return implode('|', array_filter([$field->validate, ...$more], static fn (string $rule): bool => $rule !== ''));
    }
}
