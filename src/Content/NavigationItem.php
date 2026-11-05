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
 * One link of a menu from navigation().
 */
final readonly class NavigationItem
{
    /**
     * @param list<NavigationItem> $children
     */
    public function __construct(
        public string $title,
        public string $url,
        public bool $active,
        public array $children,
    ) {
    }
}
