<?php

namespace Bonfire\Config;

use CodeIgniter\Events\Events;

/*
 * Pre Controller events
 */
Events::on('post_controller_constructor', function() {
    service('bonfire')->boot();
});

/*
 * Post System events
 */
Events::on('post_system', function () {
    /**
     * Ensure that our config values are persisted
     * to the database.
     */
    if(config('Config')->persistConfig) {
        model('ConfigModel')->persist();
    }
});
