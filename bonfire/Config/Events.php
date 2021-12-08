<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
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
