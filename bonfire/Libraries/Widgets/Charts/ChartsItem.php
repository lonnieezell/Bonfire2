<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Libraries\Widgets\Charts;

use Bonfire\Libraries\Widgets\Interfaces\Item;

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
     * @var array|string[]
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
     * @var array|string[]
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

	/**
	 * @var int
	 */
	protected int $borderWidth = 1;

	/**
	 * @var float
	 */
	protected float $tension = 0.1;

	/**
	 * @var int
	 */
	protected int $overOffset = 20;

    /**
     * @var array|string[]
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
	 * @param array|null $data
	 */
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
    }

	/**
	 * @return string|null
	 */
	public function title(): ?string
    {
        return $this->title;
    }

    /**
     * @return $this
     */
    public function setTitle(?string $title): ChartsItem
    {
        $this->title = $title;

        return $this;
    }

	/**
	 * @return string|null
	 */
	public function type(): ?string
    {
        return $this->type;
    }

    /**
     * @return $this
     */
    public function setType(?string $type): ChartsItem
    {
        $this->type = $type;

        return $this;
    }

	/**
	 * @return string|null
	 */
	public function cssClass(): ?string
    {
        return $this->cssClass;
    }

    /**
     * @return $this
     */
    public function setCssclass(string $cssClass): ChartsItem
    {
        $this->cssClass = $cssClass;

        return $this;
    }

	/**
	 * @return array|null
	 */
	public function data(): ?array
    {
        return $this->data;
    }

    /**
     * @return $this
     */
    public function setData(array $data): ChartsItem
    {
        $this->data = $data;

        return $this;
    }

	/**
	 * @return array|null
	 */
	public function label(): ?array
    {
        return $this->label;
    }

    /**
     * @return $this
     */
    public function setLabel(array $label): ChartsItem
    {
        $this->label = $label;

        return $this;
    }

	/**
	 * @return string|null
	 */
	public function chartName(): ?string
    {
        return $this->chartName;
    }


	/**
	 * @param string $chartName
	 * @return $this
	 */
	public function setChartName(string $chartName = ''): ChartsItem
    {
		$time = str_replace('.', '_', (string)microtime(true) );
        $this->chartName .= $chartName . '_' . $time;

        return $this;
    }

	/**
	 * @return string
	 */
	public function getScript(): string
    {
        return '
		const data_' . $this->chartName() . ' = ' . json_encode($this->data()) . ';
		const labels_' . $this->chartName() . ' = ' . json_encode($this->label()) . ';
		const Chart_' . $this->chartName() . " = new Chart(
			document.getElementById('" . $this->chartName() . "'),
			drawChart(data_" . $this->chartName() . ', labels_' . $this->chartName() . ", '" . $this->title() . "', '" . $this->type() . "')
		);";
    }

    /**
     * @return $this
     */
    public function addDataset(string $tableName, string $groupField, string $countField, string $selectMode = 'count'): ChartsItem
    {
        // Chart Section Begin
        $groupsData = db_connect()->table($tableName)
            ->select($groupField);

        switch ($selectMode) {
            case 'count':
                $groupsData = $groupsData->selectCount($countField);
                break;

            case 'avg':
                $groupsData = $groupsData->selectAvg($countField);
                break;

            case 'max':
                $groupsData = $groupsData->selectMax($countField);
                break;

            case 'min':
                $groupsData = $groupsData->selectMin($countField);
                // no break
            case 'sum':
                $groupsData = $groupsData->selectSum($countField);
                break;

            default:
                // TODO: return error message
                $groupsData = $groupsData->selectCount($countField);
        }

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