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
 * A group of a form's fields, shown under a title in one to three columns. It only affects how the form
 * is shown: where the values are stored doesn't depend on it.
 */
final readonly class Section
{
    public const MIN_COLUMNS     = 1;
    public const MAX_COLUMNS     = 3;
    public const DEFAULT_COLUMNS = 1;

    /**
     * @param list<Field> $fields
     */
    public function __construct(
        public ?string $title,
        public ?string $instructions,
        public int $columns,
        public array $fields,
    ) {
    }
}
