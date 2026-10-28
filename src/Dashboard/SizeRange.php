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
 * The size a widget starts at, and the presets it may be set to.
 */
final readonly class SizeRange
{
    public function __construct(
        public Size $default,
        public Size $min = Size::Small,
        public Size $max = Size::Full,
    ) {
    }
}
