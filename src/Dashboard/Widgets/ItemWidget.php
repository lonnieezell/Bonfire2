<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Dashboard\Widgets;

use Bonfire\Dashboard\DashboardWidget;
use Bonfire\Widgets\Interfaces\Item;
use Bonfire\Widgets\WidgetKind;

/**
 * Presents an item of service('widgets') as a dashboard widget.
 * Its switch in the Widgets settings decides whether it is available.
 */
abstract class ItemWidget implements DashboardWidget
{
    public function __construct(protected readonly Item $item)
    {
    }

    public static function for(Item $item): self
    {
        return match ($item->settings()->kind) {
            WidgetKind::Stats  => new StatsWidget($item),
            WidgetKind::Charts => new ChartWidget($item),
            WidgetKind::Logs   => new LogWidget($item),
        };
    }

    public function kind(): WidgetKind
    {
        return $this->item->settings()->kind;
    }

    public function id(): string
    {
        return $this->item->settings()->widgetId();
    }

    public function available(): bool
    {
        return $this->item->settings()->enabled();
    }

    /**
     * The item's own permission(), if it declares one.
     */
    public function permission(): ?string
    {
        return method_exists($this->item, 'permission') ? $this->item->permission() : null;
    }
}
