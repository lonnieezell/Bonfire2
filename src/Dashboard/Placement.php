<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Dashboard;

/**
 * One widget placed on the dashboard, at a size, with the settings it
 * sets for itself.
 */
final readonly class Placement
{
    /**
     * @param array<string, bool|float|int|string> $values Settings that differ from what the widget's fields inherit
     */
    public function __construct(
        public string $id,
        public Size $size,
        public array $values = [],
    ) {
    }
}
