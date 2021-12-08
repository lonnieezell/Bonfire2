<?php

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Config;

use CodeIgniter\Events\Events;

// Pre Controller events
Events::on('post_controller_constructor', static function () {
    service('bonfire')->boot();
});
