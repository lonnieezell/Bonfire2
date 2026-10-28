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

use Bonfire\Widgets\ItemSettings;
use Bonfire\Widgets\WidgetKind;

/**
 * Decides which widgets the viewer's dashboard shows, and in what order.
 *
 * The default layout is one untitled section holding Dashboard.cells in
 * order, the stats, logs and charts cells expanded into their available items.
 */
class LayoutResolver
{
    public function __construct(private readonly WidgetRegistry $registry)
    {
    }

    public function resolve(): Layout
    {
        $available  = $this->registry->available();
        $placements = [];

        foreach ($this->cells() as $alias) {
            $kind = WidgetRegistry::ITEM_CELLS[$alias] ?? null;
            $ids  = $kind === null
                ? ['cell:' . $alias]
                : array_filter(array_keys($available), static fn (string $id) => str_starts_with($id, strtolower($kind->value) . ':'));

            foreach ($ids as $id) {
                if (isset($available[$id])) {
                    $placements[] = new Placement($id, $available[$id]->sizes()->default);
                }
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
        [$prefix, $rest] = explode(':', $id, 2) + ['', ''];

        if ($prefix === 'cell') {
            return in_array($rest, $this->cells(), true);
        }

        $kind = WidgetKind::tryFrom(ucfirst($prefix));

        return $kind !== null
            && in_array(array_search($kind, WidgetRegistry::ITEM_CELLS, true), $this->cells(), true)
            && (new ItemSettings($kind, $rest))->enabled();
    }

    /**
     * @return list<string>
     */
    private function cells(): array
    {
        return setting('Dashboard.cells') ?? [];
    }
}
