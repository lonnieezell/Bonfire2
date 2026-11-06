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

use Bonfire\Fields\Field;
use Bonfire\Fields\FieldType;
use Bonfire\Fields\FieldTypes;

/**
 * Where an entry keeps a field's value, for any field type.
 */
final class EntryStorage
{
    /**
     * The front matter key that holds the field's value.
     */
    public static function key(FieldType $type, Field $field): string
    {
        return $type instanceof ContentFieldType ? $type->key($field) : $field->handle;
    }

    /**
     * False for a type whose value is the file's name rather than a front matter key.
     */
    public static function inFrontMatter(FieldType $type): bool
    {
        return ! $type instanceof ContentFieldType || $type->storedInFrontMatter();
    }

    /**
     * Casts a value as its field's type reads it from a file: a toggle is a bool, an integer an int, and a date
     * or datetime text. Null when it doesn't fit.
     */
    public static function cast(mixed $value, Field $field): mixed
    {
        if (! in_array($field->type, ['toggle', 'integer', 'date', 'datetime'], true)) {
            return $value;
        }

        $text = FieldTypes::get($field->type)->fromStored($value, $field);

        return match ($field->type) {
            'toggle'           => $text === '1',
            'integer'          => $text === '' ? null : (int) $text,
            'date', 'datetime' => $text === '' ? null : $text,
        };
    }
}
