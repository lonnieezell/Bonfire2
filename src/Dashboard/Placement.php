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
 * One widget placed on the dashboard, at a size.
 */
final readonly class Placement
{
    public function __construct(
        public string $id,
        public Size $size,
    ) {
    }
}
