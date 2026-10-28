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

use Bonfire\Dashboard\Size;
use Bonfire\Dashboard\SizeRange;
use Bonfire\View\Metadata;
use Bonfire\Widgets\Types\Charts\ChartsItem;

/**
 * A ChartsItem as a chart card.
 *
 * @property ChartsItem $item
 */
class ChartWidget extends ItemWidget
{
    public function title(): string
    {
        return (string) $this->item->title();
    }

    public function icon(): string
    {
        return match ($this->item->type()) {
            'line'                         => 'fas fa-chart-line',
            'bar'                          => 'fas fa-chart-bar',
            'pie', 'doughnut', 'polarArea' => 'fas fa-chart-pie',
            default                        => 'fas fa-chart-area',
        };
    }

    /**
     * The chart type in words: polarArea becomes "Polar area chart".
     */
    public function description(): string
    {
        helper('inflector');

        return lang('Bonfire.chartOfType', [ucfirst(strtolower(humanize(decamelize((string) $this->item->type()))))]);
    }

    public function group(): string
    {
        return 'charts';
    }

    /**
     * The default size follows the chart's CSS class: col-6 is Medium.
     */
    public function sizes(): SizeRange
    {
        $columns = preg_match('/^col-(\d+)$/', (string) $this->item->cssClass(), $match) ? (int) $match[1] : 0;

        return new SizeRange(Size::tryFromColumns($columns) ?? Size::Medium);
    }

    public function render(): string
    {
        /** @var Metadata $meta */
        $meta = service('viewMeta');
        ChartsItem::addAssets($meta);
        $meta->addRawScript($this->item->getScript());

        return '<div class="bf-chart">' . view('Bonfire\Widgets\Views\Cells\_chart', ['chart' => $this->item]) . '</div>';
    }
}
