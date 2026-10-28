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

/**
 * Where the settings library stores the Stats widgets' defaults
 * (Stats.stats_showLink) and every widget's enabled switch
 * (Stats.Stats_userGroups643, ...), so it must stay. The defaults are those
 * of the widgets' fields().
 */
class Stats extends BaseConfig
{
}
