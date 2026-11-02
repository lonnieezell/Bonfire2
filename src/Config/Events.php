<?php

namespace Bonfire\Config;

use Bonfire\Core\Namespaces;
use Bonfire\Email\LegacySettingsTransport;
use CodeIgniter\Events\Events;
use Myth\Postal\Email;

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

/** The From in Settings -> Email is the site-wide default for messages without one. */
Events::on('email.composing', static function (Email $email): void {
    if ($email->from !== null) {
        return;
    }

    helper('setting');

    $address = (string) setting('Email.fromEmail');

    if ($address !== '') {
        $email->from($address, (string) setting('Email.fromName'));
    }
});
