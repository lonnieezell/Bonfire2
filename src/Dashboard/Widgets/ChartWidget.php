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
    private const CHART_JS = 'https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js';

    /**
     * Adds Chart.js and its helpers to the page, once.
     */
    public static function addAssets(Metadata $meta): void
    {
        if (in_array(self::CHART_JS, array_column($meta->scripts(), 'src'), true)) {
            return;
        }

        $meta->addScript(['src' => 'https://cdn.jsdelivr.net/npm/d3-color@3']);
        $meta->addScript(['src' => 'https://cdn.jsdelivr.net/npm/d3-interpolate@3']);
        $meta->addScript(['src' => 'https://cdn.jsdelivr.net/npm/d3-scale-chromatic@3']);
        $meta->addScript([
            'src'            => self::CHART_JS,
            'integrity'      => 'sha384-jb8JQMbMoBUzgWatfe6COACi2ljcDdZQ2OxczGA3bGNeWe+6DChMTBJemed7ZnvJ',
            'crossorigin'    => 'anonymous',
            'referrerpolicy' => 'no-referrer',
        ]);
        $meta->addScript(['src' => asset('admin/js/chart.js', 'js')]);
    }

    public function title(): string
    {
        return (string) $this->item->title();
    }

    public function icon(): string
    {
        return match ($this->item->type()) {
            'line'                     => 'fas fa-chart-line',
            'bar'                      => 'fas fa-chart-bar',
            'pie', 'doughnut', 'polarArea' => 'fas fa-chart-pie',
            default                    => 'fas fa-chart-area',
        };
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
        self::addAssets($meta);
        $meta->addRawScript($this->item->getScript());

        return view('Bonfire\Dashboard\Views\widgets\chart', ['chart' => $this->item]);
    }
}
