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

/**
 * What a reindex did.
 */
final readonly class ReindexResult
{
    /**
     * @param array<string, string> $errors  why each file wasn't indexed, keyed by its path from the content root
     * @param string|null           $failure why the index wasn't changed at all, and the counts are therefore 0
     */
    public function __construct(
        public int $added = 0,
        public int $updated = 0,
        public int $removed = 0,
        public int $unchanged = 0,
        public array $errors = [],
        public ?string $failure = null,
    ) {
    }
}
