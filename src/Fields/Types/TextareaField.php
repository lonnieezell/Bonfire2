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
 * Several lines of text.
 */
class TextareaField extends FieldType
{
    public function partial(): string
    {
        return 'textarea';
    }

    /**
     * Browsers post line breaks as CRLF, and the file keeps LF.
     */
    public function prepare(mixed $input, Field $field, array $submitted): string
    {
        return str_replace("\r\n", "\n", parent::prepare($input, $field, $submitted));
    }
}
