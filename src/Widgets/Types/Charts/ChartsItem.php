<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Widgets\Types\Charts;

use Bonfire\Widgets\Interfaces\Item;
use Bonfire\Widgets\ItemSettings;
use Bonfire\Widgets\WidgetKind;
use Bonfire\Widgets\WidgetOptions;

/**
 * Represents an individual widget Charts.
 */
class ChartsItem implements Item
{
    /**
     * The 'weight' used for sorting.
     *
     * @var int|null
     */
    protected $weight;

    /**
     * @var array|list<string>
     */
    protected array $bgColor = [
        'rgba(255,  99, 132, 0.2)',
        'rgba(255, 159,  64, 0.2)',
        'rgba(255, 205,  86, 0.2)',
        'rgba( 75, 192, 192, 0.2)',
        'rgba( 54, 162, 235, 0.2)',
        'rgba(153, 102, 255, 0.2)',
        'rgba(201, 203, 207, 0.2)',
    ];

    /**
     * @var array|list<string>
     */
    protected array $borderColor = [
        'rgb(255,  99, 132)',
        'rgb(255, 159,  64)',
        'rgb(255, 205,  86)',
        'rgb( 75, 192, 192)',
        'rgb( 54, 162, 235)',
        'rgb(153, 102, 255)',
        'rgb(201, 203, 207)',
    ];

    protected int $borderWidth = 1;
    protected float $tension   = 0.1;
    protected int $overOffset  = 20;

    /**
     * @var array|list<string>
     */
    protected array $supportedTypes = [
        'line',
        'bar',
        'doughnut',
        'pie',
        'polarArea',
    ];

    /**
     * @var string|null
     */
    protected $title;

    /**
     * Unique identifier for the widget, used for storage in settings.
     *
     * @var string
     */
    protected $id;

    /**
     * @var array|string|null
     */
    protected $data;

    /**
     * @var array|string|null
     */
    protected $label;

    /**
     * @var string
     */
    protected $type = 'line';

    /**
     * @var string
     */
    protected $cssClass = 'col-6';

    /**
     * @var string
     */
    protected $chartName;

    public function __construct(?array $data = null)
    {
        if (! is_array($data)) {
            return;
        }

        foreach ($data as $key => $value) {
            $method = 'set' . ucfirst((string) $key);
            if (method_exists($this, $method)) {
                $this->{$method}($value);
            }
        }
        $this->setChartName('');
    }

    public function title(): ?string
    {
        // Outputing null seems to be demanded by the tests: chartCollectionTest::testTitles()
        return $this->title ? mb_strtoupper($this->title) : null;
    }

    public function setTitle(?string $title): ChartsItem
    {
        $this->title = $title;

        return $this;
    }

    public function id(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): ChartsItem
    {
        $this->id = $id;

        return $this;
    }

    public function type(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): ChartsItem
    {
        $this->type = $type;

        return $this;
    }

    public function cssClass(): ?string
    {
        return $this->cssClass;
    }

    public function setCssclass(string $cssClass): ChartsItem
    {
        $this->cssClass = $cssClass;

        return $this;
    }

    public function data(): ?array
    {
        return $this->data;
    }

    public function setData(array $data): ChartsItem
    {
        $this->data = $data;

        return $this;
    }

    public function label(): ?array
    {
        return $this->label;
    }

    public function setLabel(array $label): ChartsItem
    {
        $this->label = $label;

        return $this;
    }

    public function chartName(): ?string
    {
        return $this->chartName;
    }

    public function setChartName(string $chartName = ''): ChartsItem
    {
        $this->chartName .= $chartName . '_' . hrtime(true);

        return $this;
    }

    public function settings(): ItemSettings
    {
        return new ItemSettings(WidgetKind::Charts, $this->id);
    }

    public function getScript(): string
    {
        $options = WidgetOptions::forChartType($this->type());

        $flag = static fn (string $name): string => $options?->get($name) ? 'true' : 'null';
        $text = static function (string $name) use ($options): string {
            $value = $options?->get($name);

            return $value ? "'" . $value . "'" : 'null';
        };

        $line_tension    = $options?->get('tension') ?: 'null';
        $borderWidth     = $options?->get('borderWidth') ?: 'null';
        $enableAnimation = $flag('enableAnimation');
        $showTitle       = $flag('showTitle');
        $showSubTitle    = $flag('showSubTitle');
        $showLegend      = $flag('showLegend');
        $legendPosition  = $text('legendPosition');

        $scheme          = $text('colorScheme');
        $backgroundColor = $scheme !== 'null' ? $scheme : $text('borderColor');
        // A bar chart has always taken a lowercase border colour from the scheme name
        $borderColor = $this->type() === 'bar' ? strtolower(rtrim($backgroundColor, 's')) : $backgroundColor;

        if (str_replace("'", '', $backgroundColor) !== 'null') {
            $backgroundColor = 'const backgroundColor_' . $this->chartName() . ' = d3.scheme' . str_replace("'", '', $backgroundColor) . '[9];';
            $borderColor     = 'const borderColor_' . $this->chartName() . ' = ' . $borderColor . ';';
        } else {
            $backgroundColor = 'const backgroundColor_' . $this->chartName() . ' = ' . str_replace("'", '', $backgroundColor) . ';';
            $borderColor     = 'const borderColor_' . $this->chartName() . ' = null;';
        }

        return '
            const data_' . $this->chartName() . ' = ' . json_encode($this->data()) . ';
            const labels_' . $this->chartName() . ' = ' . json_encode($this->label()) . ';
            ' . $backgroundColor . '
            ' . $borderColor . '
            const Chart_' . $this->chartName() . " = new Chart(
                document.getElementById('" . $this->chartName() . "'),
                drawChart( data_" . $this->chartName() . ', labels_' . $this->chartName() . ", '" . $this->title() . "', '" . $this->type() . "', " . $line_tension . ', backgroundColor_' . $this->chartName() . ' , borderColor_' . $this->chartName() . ', ' . $borderWidth . ', ' . $enableAnimation . ', ' . $showTitle . ',	' . $showSubTitle . ',	' . $showLegend . ',  ' . $legendPosition . ')
            );';
    }

    public function addDataset(string $tableName, string $groupField, string $countField, string $selectMode = 'count'): ChartsItem
    {
        // Only fetch the data on the dashboard, and when this chart is enabled
        if (! service('dashboardContext')->shows($this->settings())) {
            return $this;
        }

        // Chart Section Begin
        $groupsData = db_connect()->table($tableName)
            ->select($groupField);

        $groupsData = match ($selectMode) {
            'count' => $groupsData->selectCount($countField),
            'avg'   => $groupsData->selectAvg($countField),
            'max'   => $groupsData->selectMax($countField),
            'min'   => $groupsData->selectMin($countField),
            'sum'   => $groupsData->selectSum($countField),
            // TODO: return error message
            default => $groupsData->selectCount($countField),
        };

        $groupsData = $groupsData->groupBy($groupField)
            ->get()
            ->getResultArray();

        // Prepare Data
        $data  = [];
        $label = [];

        foreach ($groupsData as $el) {
            $data[]  = $el[$countField];
            $label[] = $el[$groupField];
        }

        $this->setData($data);
        $this->setLabel($label);

        return $this;
    }
}
