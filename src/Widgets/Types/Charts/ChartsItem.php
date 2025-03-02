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

    /**
     * @var bool
     */
    protected $dashboardRoute = false;

    public function __construct(?array $data = null)
    {
        if (! is_array($data)) {
            return;
        }

        foreach ($data as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->{$method}($value);
            }
        }
        $this->setChartName('');

        // true if we are on Dashboard page
        $this->dashboardRoute = current_url() === config('App')->baseURL . '/' . ADMIN_AREA;
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

    public function getScript(): string
    {
        $line_tension    = 'null';
        $backgroundColor = 'null';
        $borderColor     = 'null';
        $borderWidth     = 'null';
        $enableAnimation = 'null';
        $showTitle       = 'null';
        $showSubTitle    = 'null';
        $showLegend      = 'null';
        $legendPosition  = 'null';

        switch ($this->type()) {
            case 'line':
                $line_tension    = setting()->get('LineChart.' . $this->type() . '_tension') ?: 'null';
                $borderColor     = setting()->get('LineChart.' . $this->type() . '_borderColor') ? "'" . setting()->get('LineChart.' . $this->type() . '_borderColor') . "'" : 'null';
                $backgroundColor = $borderColor;
                $borderWidth     = setting()->get('LineChart.' . $this->type() . '_borderWidth') ?: 'null';
                $enableAnimation = setting()->get('LineChart.' . $this->type() . '_enableAnimation') ? 'true' : 'null';
                $showTitle       = setting()->get('LineChart.' . $this->type() . '_showTitle') ? 'true' : 'null';
                $showSubTitle    = setting()->get('LineChart.' . $this->type() . '_showSubTitle') ? 'true' : 'null';
                $showLegend      = setting()->get('LineChart.' . $this->type() . '_showLegend') ? 'true' : 'null';
                $legendPosition  = setting()->get('LineChart.' . $this->type() . '_legendPosition') ? "'" . setting()->get('LineChart.' . $this->type() . '_legendPosition') . "'" : 'null';
                break;

            case 'bar':
                $enableAnimation = setting()->get('BarChart.' . $this->type() . '_enableAnimation') ? 'true' : 'null';
                $showTitle       = setting()->get('BarChart.' . $this->type() . '_showTitle') ? 'true' : 'null';
                $showLegend      = setting()->get('BarChart.' . $this->type() . '_showLegend') ? 'true' : 'null';
                $legendPosition  = setting()->get('BarChart.' . $this->type() . '_legendPosition') ? "'" . setting()->get('BarChart.' . $this->type() . '_legendPosition') . "'" : 'null';
                $backgroundColor = setting()->get('BarChart.' . $this->type() . '_colorScheme') ? "'" . setting()->get('BarChart.' . $this->type() . '_colorScheme') . "'" : 'null';
                $borderColor     = strtolower(rtrim($backgroundColor, 's'));
                break;

            case 'doughnut':
                $enableAnimation = setting()->get('DoughnutChart.' . $this->type() . '_enableAnimation') ? 'true' : 'null';
                $showTitle       = setting()->get('DoughnutChart.' . $this->type() . '_showTitle') ? 'true' : 'null';
                $showLegend      = setting()->get('DoughnutChart.' . $this->type() . '_showLegend') ? 'true' : 'null';
                $legendPosition  = setting()->get('DoughnutChart.' . $this->type() . '_legendPosition') ? "'" . setting()->get('DoughnutChart.' . $this->type() . '_legendPosition') . "'" : 'null';
                $backgroundColor = setting()->get('DoughnutChart.' . $this->type() . '_colorScheme') ? "'" . setting()->get('DoughnutChart.' . $this->type() . '_colorScheme') . "'" : 'null';
                $borderColor     = $backgroundColor;
                break;

            case 'pie':
                $enableAnimation = setting()->get('PieChart.' . $this->type() . '_enableAnimation') ? 'true' : 'null';
                $showTitle       = setting()->get('PieChart.' . $this->type() . '_showTitle') ? 'true' : 'null';
                $showLegend      = setting()->get('PieChart.' . $this->type() . '_showLegend') ? 'true' : 'null';
                $legendPosition  = setting()->get('PieChart.' . $this->type() . '_legendPosition') ? "'" . setting()->get('PieChart.' . $this->type() . '_legendPosition') . "'" : 'null';
                $backgroundColor = setting()->get('PieChart.' . $this->type() . '_colorScheme') ? "'" . setting()->get('PieChart.' . $this->type() . '_colorScheme') . "'" : 'null';
                $borderColor     = $backgroundColor;
                break;

            case 'polarArea':
                $enableAnimation = setting()->get('PolarAreaChart.' . $this->type() . '_enableAnimation') ? 'true' : 'null';
                $showTitle       = setting()->get('PolarAreaChart.' . $this->type() . '_showTitle') ? 'true' : 'null';
                $showLegend      = setting()->get('PolarAreaChart.' . $this->type() . '_showLegend') ? 'true' : 'null';
                $legendPosition  = setting()->get('PolarAreaChart.' . $this->type() . '_legendPosition') ? "'" . setting()->get('PolarAreaChart.' . $this->type() . '_legendPosition') . "'" : 'null';
                $backgroundColor = setting()->get('PolarAreaChart.' . $this->type() . '_colorScheme') ? "'" . setting()->get('PolarAreaChart.' . $this->type() . '_colorScheme') . "'" : 'null';
                $borderColor     = $backgroundColor;
                break;
        }

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
        // Check if we are on Dashboard page and the chart is enabled, only proceed if both true
        if (
            current_url() !== config('App')->baseURL . '/' . ADMIN_AREA
            || setting('Stats.Charts_' . $this->id) !== 'on'
        ) {
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
