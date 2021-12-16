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
     * Bezier curve tension of the line. Set to 0 to draw straightlines. This option is ignored if monotone cubic interpolation is used.
     * --------------------------------------------------------------------------
     */
    public float $line_tension = 0.1;

    /**
     * --------------------------------------------------------------------------
     * Enable the chart animation
     * --------------------------------------------------------------------------
     */
    public bool $line_enableAnimation = true;

    /**
     * --------------------------------------------------------------------------
     * Show the chart title
     * --------------------------------------------------------------------------
     */
    public bool $line_showTitle = true;

    /**
     * --------------------------------------------------------------------------
     * Show the chart legend
     * --------------------------------------------------------------------------
     */
    public bool $line_showLegend = true;

    /**
     * --------------------------------------------------------------------------
     * Set the legend position
     *
     * possible value are:
     * top
     * left
     * bottom
     * right
     * --------------------------------------------------------------------------
     */
    public string $line_legendPosition = 'bottom';
}
