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

/**
 * One field of a form, such as a blueprint's.
 */
final readonly class Field
{
    /**
     * @param list<string>|string  $validate CodeIgniter validation rules, as a `a|b` string or a list
     * @param array<string, mixed> $options  the keys the type reads itself, such as `rows` or `from`
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
}
