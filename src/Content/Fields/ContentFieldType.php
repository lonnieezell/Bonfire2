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

/**
 * What a field type tells Content about where its value lives in an entry. A type that doesn't implement it
 * keeps its value in the front matter, under the field's handle.
 */
interface ContentFieldType
{
    /**
     * The front matter key that holds the field's value, which is the field's handle unless the type says otherwise.
     */
    public function key(Field $field): string;

    /**
     * False for a type whose value is the file's name rather than a front matter key.
     */
    public function storedInFrontMatter(): bool;
}
