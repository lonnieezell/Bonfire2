<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Recycler\Widgets;

use Bonfire\Dashboard\DashboardWidget;
use Bonfire\Dashboard\Size;
use Bonfire\Dashboard\SizeRange;
use Bonfire\Dashboard\WidgetValues;

/**
 * The "Recycler" dashboard widget: how many deleted records each resource
 * registered with `service('recycler')` holds, leaving out those with none.
 */
class RecyclerSummaryWidget implements DashboardWidget
{
    public const ID = 'recycler:summary';

    public function id(): string
    {
        return self::ID;
    }

    public function title(): string
    {
        return lang('Recycler.recyclerModTitle');
    }

    public function icon(): string
    {
        return 'fas fa-recycle';
    }

    public function group(): string
    {
        return lang('Bonfire.toolsModTitle');
    }

    public function description(): string
    {
        return lang('Recycler.summaryCard');
    }

    public function sizes(): SizeRange
    {
        return new SizeRange(Size::Half, Size::Third);
    }

    public function permission(): ?string
    {
        return 'recycler.view';
    }

    public function fields(): array
    {
        return [];
    }

    public function render(WidgetValues $values): string
    {
        $rows = [];

        foreach (service('recycler')->resources() as $resource) {
            $count = $resource->deletedCount();

            if ($count > 0) {
                $rows[] = ['alias' => $resource->alias(), 'label' => $resource->label(), 'count' => $count];
            }
        }

        return view('Bonfire\Recycler\Views\widgets\summary', [
            'title' => $this->title(),
            'rows'  => $rows,
        ]);
    }
}
