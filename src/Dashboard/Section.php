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
 * A run of widgets on the dashboard. An untitled one shows no heading.
 */
final readonly class Section
{
    /**
     * @param list<Placement> $widgets
     */
    public function __construct(
        public string $title,
        public array $widgets,
    ) {
    }
}
