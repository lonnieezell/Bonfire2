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

class BarChart extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Enable the chart animation
     * --------------------------------------------------------------------------
     */
    public bool $bar_enableAnimation = true;

    /**
     * --------------------------------------------------------------------------
     * Show the chart title
     * --------------------------------------------------------------------------
     */
    public bool $bar_showTitle = true;

    /**
     * --------------------------------------------------------------------------
     * Show the chart legend
     * --------------------------------------------------------------------------
     */
    public bool $bar_showLegend = true;

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
    public string $bar_legendPosition = 'bottom';

    /**
     * --------------------------------------------------------------------------
     * Set the default color scheme to fill the chart
     *
     * possible value are:
     * null
     * Blues
     * Greens
     * Greys
     * Oranges
     * Purples
     * Reds
     * --------------------------------------------------------------------------
     */
    public string $bar_colorScheme = 'null';
}
