<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Tools\Config;

use CodeIgniter\Config\BaseConfig;

class Logs extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Pagination Limit
     * --------------------------------------------------------------------------
     *
     * Limit logs results per page
     */
    public $paginationLimit = 15;
}
