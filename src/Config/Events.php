<?php

namespace Bonfire\Config;

use Bonfire\Core\Namespaces;
use Bonfire\Email\LegacySettingsTransport;
use Bonfire\Email\Outbox;
use CodeIgniter\Events\Events;
use Myth\Postal\Address;
use Myth\Postal\Email;
use Myth\Postal\SendResult;

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

    // fromEmail may be "Name <address>" outside the settings form, as setFrom() allows.
    $address = Address::fromString((string) setting('Email.fromEmail'))->email;

    if ($address !== '') {
        $email->from($address, (string) setting('Email.fromName'));
    }
});

/** The Outbox logs every send Postal reports as sent or failed, never cancelled or suppressed ones. */
$logSend = static function (Email $email, SendResult $result): void {
    (new Outbox())->record($email, $result);
};

Events::on('email.sent', $logSend);
Events::on('email.failed', $logSend);
