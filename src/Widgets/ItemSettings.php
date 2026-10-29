<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Widgets;

/**
 * Names one widget item on the dashboard.
 *
 * Its widget id (stats:<id>, charts:<id>) is in the layouts installs have
 * saved, so it must not change.
 */
final readonly class ItemSettings
{
    public function __construct(
        public WidgetKind $kind,
        public ?string $id,
    ) {
    }

    /**
     * The item's id on the dashboard, e.g. "stats:usersInRecycler693".
     */
    public function widgetId(): string
    {
        return strtolower($this->kind->value) . ':' . $this->id;
    }
}
