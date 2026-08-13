<?php

namespace Bonfire\Config;

use Bonfire\Core\Namespaces;
use CodeIgniter\Events\Events;

/** Registers all Bonfire Module namespaces and app module namespaces */
Events::on('pre_system', static function (): void {
    Namespaces::register();
});

Events::on('pre_command', static function (): void {
    Namespaces::register();
});
