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

class ChartDataset
{
    /**
     * The label for the dataset which appears in the legend and tooltips.
     */
    protected string $label;

    protected array $data;
    protected array $backgroundColor = [
        'rgba(255,  99, 132, 0.2)',
        'rgba(255, 159,  64, 0.2)',
        'rgba(255, 205,  86, 0.2)',
        'rgba( 75, 192, 192, 0.2)',
        'rgba( 54, 162, 235, 0.2)',
        'rgba(153, 102, 255, 0.2)',
        'rgba(201, 203, 207, 0.2)',
    ];
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
    protected int $hoverOffset = 20;

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
    }

    /**
     * Get the label for the dataset which appears in the legend and tooltips.
     */
    public function label(): string
    {
        return $this->label;
    }

    public function data(): array
    {
        return $this->data;
    }

    /**
     * @return array|string[]
     */
    public function backgroundColor(): array
    {
        return $this->backgroundColor;
    }

    /**
     * @return array|string[]
     */
    public function borderColor(): array
    {
        return $this->borderColor;
    }

    public function borderWidth(): int
    {
        return $this->borderWidth;
    }

    public function tension(): float
    {
        return $this->tension;
    }

    public function hoverOffset(): int
    {
        return $this->hoverOffset;
    }

    /**
     * Set the label for the dataset which appears in the legend and tooltips.
     *
     * @param mixed $label
     */
    public function setLabel($label): ChartDataset
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @param mixed $data
     */
    public function setData($data): ChartDataset
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param mixed $backgroundColor
     */
    public function setBackgroundColor($backgroundColor): ChartDataset
    {
        $this->backgroundColor = $backgroundColor;

        return $this;
    }

    /**
     * @param mixed $borderColor
     */
    public function setBorderColor($borderColor): ChartDataset
    {
        $this->borderColor = $borderColor;

        return $this;
    }

    /**
     * @param mixed $borderWidth
     */
    public function setBorderWidth($borderWidth): ChartDataset
    {
        $this->borderWidth = $borderWidth;

        return $this;
    }

    /**
     * @param mixed $tension
     */
    public function setTension($tension): ChartDataset
    {
        $this->tension = $tension;

        return $this;
    }

    /**
     * @param mixed $hoverOffset
     */
    public function setHoverOffset($hoverOffset): ChartDataset
    {
        $this->hoverOffset = $hoverOffset;

        return $this;
    }
}
