<?php

namespace Bonfire\Config;

use CodeIgniter\Events\Events;

Events::on('post_system', function () {
    /**
     * Ensure that our config values are persisted
     * to the database.
     */
    if(config('Config')->persistConfig) {
        model('ConfigModel')->persist();
    }
});
