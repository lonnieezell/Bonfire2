<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Modules\Widgets\Config;

use CodeIgniter\Config\BaseConfig;

class LineChart extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * The Background color
     * --------------------------------------------------------------------------
     *
     * @var array|string[]
     */
    public array $bgColor = [
        'rgba(255,  99, 132, 0.2)',
        'rgba(255, 159,  64, 0.2)',
        'rgba(255, 205,  86, 0.2)',
        'rgba( 75, 192, 192, 0.2)',
        'rgba( 54, 162, 235, 0.2)',
        'rgba(153, 102, 255, 0.2)',
        'rgba(201, 203, 207, 0.2)',
    ];

    /**
     * --------------------------------------------------------------------------
     * The Border color.
     * --------------------------------------------------------------------------
     *
     * @var array|string[]
     */
    public array $borderColor = [
        'rgb(255,  99, 132)',
        'rgb(255, 159,  64)',
        'rgb(255, 205,  86)',
        'rgb( 75, 192, 192)',
        'rgb( 54, 162, 235)',
        'rgb(153, 102, 255)',
        'rgb(201, 203, 207)',
    ];

    public int $line_borderWidth = 1;
    public int $overOffset       = 20;

    /**
     * --------------------------------------------------------------------------
     * Type of supported Charts
     * --------------------------------------------------------------------------
     *
     * @var array|string[]
     */
    public array $supportedTypes = [
        'line',
        'bar',
        'doughnut',
        'pie',
        'polarArea',
    ];

    /**
     * --------------------------------------------------------------------------
     * The fill color for points
     * --------------------------------------------------------------------------
     */
    public string $line_pointBackgroundColor = '#ff0000';

    /**
     * The fill color for points
     */
    public string $line_pointBorderColor = '#000000';

    /**
     * --------------------------------------------------------------------------
     * The width of the point border in pixels.
     * --------------------------------------------------------------------------
     */
    public int $line_pointBorderWidth = 2;

    /**
     * --------------------------------------------------------------------------
     * Bezier curve tension of the line. Set to 0 to draw straightlines. This option is ignored if monotone cubic interpolation is used.
     * --------------------------------------------------------------------------
     */
    public float $line_tension = 0.1;
}
