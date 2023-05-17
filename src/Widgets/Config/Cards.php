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

class Cards extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Show the Link "View Detail"
     * --------------------------------------------------------------------------
     */
    public bool $cards_showLink = true;
}
