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
use Bonfire\Widgets\ItemSettings;

/**
 * Decides which widgets the viewer's dashboard shows, and in what order.
 *
 * A user's own Dashboard.layout comes first. Otherwise the default layout
 * is one untitled section holding the plain cells of Dashboard.cells in
 * order; stats, charts and log items are added from the palette.
 */
class LayoutResolver
{
    public function __construct(private readonly WidgetRegistry $registry)
    {
    }

    public function resolve(): Layout
    {
        $stored = $this->stored();

        return $stored === null ? $this->derived() : (new LayoutValidator($this->registry))->validate($stored);
    }

    private function derived(): Layout
    {
        $available  = $this->registry->available();
        $placements = [];

        foreach ($this->cells() as $alias) {
            $widget = $available[CellWidget::idFor($alias)] ?? null;

            if ($widget !== null) {
                $placements[] = new Placement($widget->id(), $widget->sizes()->default);
            }
        }

        return new Layout([new Section('', $placements)]);
    }

    /**
     * Whether the widget is in the resolved layout.
     *
     * Items ask this from initAdmin(), before every module has registered
     * its items, so it is answered from the settings alone.
     */
    public function includes(string $id): bool
    {
        $item   = ItemSettings::fromWidgetId($id);
        $stored = $this->stored();

        if ($stored !== null) {
            $ids = array_merge(...array_map(
                static fn (array $section) => array_column($section['widgets'], 'id'),
                array_values($stored['sections']),
            ));

            return in_array($id, $ids, true) && ($item === null || $item->enabled());
        }

        return $item === null && in_array(CellWidget::aliasOf($id), $this->cells(), true);
    }

    /**
     * The settings context of the signed-in user's own layout.
     */
    public static function userContext(): ?string
    {
        $userId = auth()->id();

        return $userId === null ? null : 'user:' . $userId;
    }

    /**
     * The viewer's own layout as stored, if they saved one that still reads.
     * One that does not falls back to the default.
     */
    private function stored(): ?array
    {
        $context = self::userContext();
        $layout  = $context === null ? null : setting()->get('Dashboard.layout', $context);

        return LayoutValidator::wellFormed($layout) ? $layout : null;
    }

    /**
     * @return list<string>
     */
    private function cells(): array
    {
        return setting('Dashboard.cells') ?? [];
    }
}
