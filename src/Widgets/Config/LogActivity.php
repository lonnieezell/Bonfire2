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

class LogActivity extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Leave Debug entries out of the chart
     * --------------------------------------------------------------------------
     */
    public bool $logActivity_hideDebug = false;

    /**
     * --------------------------------------------------------------------------
     * How many of the last days to chart (1 to 31)
     * --------------------------------------------------------------------------
     */
    public int $logActivity_fileCount = 7;
}
