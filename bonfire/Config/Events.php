<?php

namespace Bonfire\Config;

use CodeIgniter\Events\Events;

/*
 * Pre Controller events
 */
Events::on('post_controller_constructor', function() {
    service('bonfire')->boot();
});
