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
     * The type keys a widget's group() may give instead of a module name.
     */
    public const TYPE_GROUPS = ['quickLinks', 'stats', 'charts', 'custom'];

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
     * The available widgets by library heading, in name order.
     *
     * @return array<string, list<DashboardWidget>>
     */
    public function grouped(): array
    {
        $groups = [];

        foreach ($this->available() as $widget) {
            $group = $widget->group();
            $group = in_array($group, self::TYPE_GROUPS, true) ? lang('Bonfire.group' . ucfirst($group)) : $group;

            $groups[$group][] = $widget;
        }

        uksort($groups, strnatcasecmp(...));

        return $groups;
    }

    /**
     * Every widget, whether available or not, by id.
     *
     * @return array<string, DashboardWidget>
     */
    public function all(): array
    {
        $widgets = $this->registered;

        foreach (service('widgets')->items() as $item) {
            $widget = ItemWidget::for($item);
            $widgets[$widget->id()] ??= $widget;
        }

        foreach (setting('Dashboard.cells') ?? [] as $alias) {
            if (! isset(self::ITEM_CELLS[$alias])) {
                $widget = new CellWidget($alias);
                $widgets[$widget->id()] ??= $widget;
            }
        }

        return $widgets;
    }
}
