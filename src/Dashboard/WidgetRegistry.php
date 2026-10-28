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

use Bonfire\Dashboard\Widgets\CellWidget;
use Bonfire\Dashboard\Widgets\ItemWidget;
use Bonfire\Widgets\WidgetKind;

/**
 * Every widget the dashboard can show, reached through service('dashboard').
 *
 * Besides the widgets modules register, it presents each item of
 * service('widgets') and each other cell of Dashboard.cells as a widget.
 */
class WidgetRegistry
{
    /**
     * The Dashboard.cells entries that stand for every item of one kind.
     */
    public const ITEM_CELLS = [
        'Bonfire\Widgets\Cells\WidgetCells::stats'  => WidgetKind::Stats,
        'Bonfire\Widgets\Cells\WidgetCells::logs'   => WidgetKind::Logs,
        'Bonfire\Widgets\Cells\WidgetCells::charts' => WidgetKind::Charts,
    ];

    /**
     * @var array<string, DashboardWidget>
     */
    private array $registered = [];

    public function register(DashboardWidget $widget): static
    {
        $this->registered[$widget->id()] = $widget;

        return $this;
    }

    public function widget(string $id): ?DashboardWidget
    {
        return $this->all()[$id] ?? null;
    }

    /**
     * The widgets a site admin has not switched off and the viewer may see, by id.
     *
     * @return array<string, DashboardWidget>
     */
    public function available(): array
    {
        $user = auth()->user();

        return array_filter($this->all(), static fn (DashboardWidget $widget) => (! $widget instanceof ItemWidget || $widget->available())
            && ($widget->permission() === null || $user?->can($widget->permission())));
    }

    /**
     * The available widgets by palette group, in the palette's order.
     *
     * @return array<string, list<DashboardWidget>>
     */
    public function grouped(): array
    {
        $groups = ['quickLinks' => [], 'stats' => [], 'charts' => [], 'custom' => []];

        foreach ($this->available() as $widget) {
            $groups[isset($groups[$widget->group()]) ? $widget->group() : 'custom'][] = $widget;
        }

        return array_filter($groups);
    }

    /**
     * @return array<string, DashboardWidget>
     */
    private function all(): array
    {
        $widgets = $this->registered;

        foreach (service('widgets')->items() as $item) {
            $widget                   = ItemWidget::for($item);
            $widgets[$widget->id()] ??= $widget;
        }

        foreach (setting('Dashboard.cells') ?? [] as $alias) {
            if (! isset(self::ITEM_CELLS[$alias])) {
                $widget                   = new CellWidget($alias);
                $widgets[$widget->id()] ??= $widget;
            }
        }

        return $widgets;
    }
}
