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
}
