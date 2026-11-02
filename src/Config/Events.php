<?php

namespace Bonfire\Config;

use Bonfire\Core\Namespaces;
use Bonfire\Email\LegacySettingsTransport;
use CodeIgniter\Events\Events;

/**
 * Registers all Bonfire Module namespaces and app module namespaces, then the
 * legacy mail bridge, which needs them to tell whether a module has a Mailer config
 */
Events::on('pre_system', static function (): void {
    Namespaces::register();
    LegacySettingsTransport::register();
});

Events::on('pre_command', static function (): void {
    Namespaces::register();
    LegacySettingsTransport::register();
});
