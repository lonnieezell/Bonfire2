<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Email;

use CodeIgniter\Database\BaseBuilder;

/**
 * The transport settings the old Settings -> Email form saved to the database.
 * They may hold a plain text SMTP password until `bonfire:email-env --purge` runs.
 */
final class StoredTransportSettings
{
    /**
     * Those settings as a new CodeIgniter install's Config\Email has them.
     */
    public const FRAMEWORK_DEFAULTS = [
        'protocol'      => 'mail',
        'mailPath'      => '/usr/sbin/sendmail',
        'SMTPHost'      => '',
        'SMTPPort'      => 25,
        'SMTPUser'      => '',
        'SMTPPass'      => '',
        'SMTPCrypto'    => 'tls',
        'SMTPTimeout'   => 5,
        'SMTPKeepAlive' => false,
    ];

    /**
     * Their rows in every context, so no stored password is left behind.
     */
    public static function rows(): BaseBuilder
    {
        $config = config('Settings');

        return db_connect($config->database['group'])
            ->table($config->database['table'])
            ->where('class', config('Email')::class)
            ->whereIn('key', array_keys(self::FRAMEWORK_DEFAULTS));
    }
}
