<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Content;

use Bonfire\Content\Fields\FieldTypes;

/**
 * One field of a blueprint.
 */
final readonly class Field
{
    /**
     * @param list<string>|string  $validate CodeIgniter validation rules, as a `a|b` string or a list
     * @param array<string, mixed> $options  the blueprint keys the type reads itself, such as `rows` or `from`
     */
    public function __construct(
        public string $handle,
        public string $type,
        public string $label,
        public array|string $validate,
        public ?string $instructions,
        public array $options,
    ) {
    }

    /**
     * Casts a value as the field's type reads it from a file: a toggle is a bool, an integer an int, and a date
     * or datetime text. Null when it doesn't fit. Any other type's value is returned as the file holds it.
     */
    public function cast(mixed $value): mixed
    {
        $text = FieldTypes::get($this->type)->fromFrontMatter($value, $this);

        return match ($this->type) {
            'toggle'           => $text === '1',
            'integer'          => $text === '' ? null : (int) $text,
            'date', 'datetime' => $text === '' ? null : $text,
            default            => $value,
        };
    }
}
