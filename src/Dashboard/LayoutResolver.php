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

/**
 * Decides which widgets the viewer's dashboard shows, and in what order.
 *
 * A user's own Dashboard.layout comes first. Otherwise the default layout
 * is the site's Dashboard.layout; without one, it is one untitled section
 * holding the plain cells of Dashboard.cells in order.
 */
class LayoutResolver
{
    public function __construct(private readonly WidgetRegistry $registry)
    {
    }

    public function resolve(): Layout
    {
        $stored = $this->stored();

        return $stored === null ? $this->defaultLayout() : (new LayoutValidator($this->registry))->validate($stored);
    }

    /**
     * The layout of a user who has not saved their own.
     */
    public function defaultLayout(): Layout
    {
        $site = $this->site();

        return $site === null ? $this->derived() : (new LayoutValidator($this->registry))->validate($site);
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
        $stored = $this->stored() ?? $this->site();

        if ($stored !== null) {
            $ids = array_merge(...array_map(
                static fn (array $section) => array_column($section['widgets'], 'id'),
                array_values($stored['sections']),
            ));

            return in_array($id, $ids, true);
        }

        return in_array(CellWidget::aliasOf($id), $this->cells(), true);
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
     * The site's default layout as stored or configured, if it reads.
     */
    private function site(): ?array
    {
        $layout = setting('Dashboard.layout');

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
