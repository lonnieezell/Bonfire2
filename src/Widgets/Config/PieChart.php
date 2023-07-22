<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Widgets\Config;

use CodeIgniter\Config\BaseConfig;

class PieChart extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Enable the chart animation
     * --------------------------------------------------------------------------
     */
    public bool $pie_enableAnimation = true;

    /**
     * --------------------------------------------------------------------------
     * Show the chart title
     * --------------------------------------------------------------------------
     */
    public bool $pie_showTitle = true;

    /**
     * --------------------------------------------------------------------------
     * Show the chart legend
     * --------------------------------------------------------------------------
     */
    public bool $pie_showLegend = true;

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
    public string $pie_legendPosition = 'bottom';

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
    public string $pie_colorScheme = 'null';
}
