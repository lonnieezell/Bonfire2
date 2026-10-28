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
use Bonfire\Dashboard\WidgetField;
use Bonfire\Dashboard\WidgetValues;
use Bonfire\View\Metadata;
use Bonfire\Widgets\Types\Charts\ChartsItem;
use Bonfire\Widgets\WidgetOptions;

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
     * The default size follows the chart's CSS class: col-6 is Half.
     */
    public function sizes(): SizeRange
    {
        $columns = preg_match('/^col-(\d+)$/', (string) $this->item->cssClass(), $match) ? (int) $match[1] : 0;

        return new SizeRange(Size::tryFromColumns($columns) ?? Size::Half);
    }

    /**
     * The options of its chart type in Settings → Widgets: a line's tension, the others' color scheme.
     */
    public function fields(): array
    {
        $type    = (string) $this->item->type();
        $options = WidgetOptions::forChartType($type);

        if ($options === null) {
            return [];
        }

        return [
            WidgetField::toggle('showTitle', lang('Widgets.showTitle'), default: true, key: $options->key('showTitle')),
            WidgetField::toggle('showLegend', lang('Widgets.showLegend'), default: true, key: $options->key('showLegend')),
            WidgetField::choice('legendPosition', lang('Widgets.legendPosition'), lang('Widgets.legendPositions'), default: 'bottom', key: $options->key('legendPosition')),
            WidgetField::toggle('enableAnimation', lang('Widgets.enableAnimation'), default: true, key: $options->key('enableAnimation')),
            $type === 'line'
                ? WidgetField::number('tension', lang('Widgets.lineTension'), default: 0.1, min: 0, step: 0.1, key: $options->key('tension'), description: lang('Widgets.lineTensionHint'))
                : WidgetField::choice('colorScheme', lang('Widgets.colorScheme'), lang('Widgets.colorSchemes'), default: 'null', key: $options->key('colorScheme')),
        ];
    }

    public function render(WidgetValues $values): string
    {
        /** @var Metadata $meta */
        $meta = service('viewMeta');
        ChartsItem::addAssets($meta);
        $meta->addRawScript($this->item->getScript($values->all()));

        return '<div class="bf-chart">' . view('Bonfire\Widgets\Views\Cells\_chart', ['chart' => $this->item]) . '</div>';
    }
}
