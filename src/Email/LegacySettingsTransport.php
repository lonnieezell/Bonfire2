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

use Myth\Postal\Config\Mailer as PostalMailer;
use Myth\Postal\Email;
use Myth\Postal\LegacyConfigMapper;
use Myth\Postal\SendResult;
use Myth\Postal\Transport\TransportInterface;

/**
 * Keeps an upgraded install's mail going out while it has no Mailer config of
 * its own, by sending through the transport its setting('Email.*') values
 * describe. They are read at send time, so booting never touches the database.
 *
 * @deprecated Removed in 2.0. Move to app/Config/Mailer.php.
 */
final class LegacySettingsTransport implements TransportInterface
{
    /**
     * The Config\Email keys Postal's mapper reads.
     */
    private const SETTINGS = [
        'protocol',
        'mailPath',
        'SMTPHost',
        'SMTPUser',
        'SMTPPass',
        'SMTPPort',
        'SMTPTimeout',
        'SMTPKeepAlive',
        'SMTPCrypto',
        'SMTPAuthMethod',
        'DSN',
    ];

    /**
     * The mailer definition $transport was built from.
     *
     * @var array<string, mixed>|null
     */
    private ?array $mailer = null;

    private ?TransportInterface $transport = null;
    private bool $noticeLogged             = false;

    /**
     * Makes the bridge the default mailer, unless the app has its own Mailer
     * config. Then it steps aside, even if that config's default is `null`.
     */
    public static function register(): void
    {
        $config = config('Mailer');

        if ($config::class !== PostalMailer::class) {
            return;
        }

        $config->transports['bonfire-legacy'] = self::class;
        $config->mailers['legacy']            = ['transport' => 'bonfire-legacy'];
        $config->default                      = 'legacy';
    }

    public function send(Email $email): SendResult
    {
        if (! $this->noticeLogged) {
            $this->noticeLogged = true;

            log_message('notice', 'Bonfire is sending mail through its deprecated legacy settings bridge, which is removed in 2.0. Run `php spark bonfire:email-env` to move to app/Config/Mailer.php.');
        }

        return $this->transport()->send($email);
    }

    public function ping(): bool
    {
        return $this->transport()->ping();
    }

    /**
     * The transport the current settings describe. It is kept while they stay
     * the same, so an SMTP keep-alive connection lasts between sends.
     */
    private function transport(): TransportInterface
    {
        helper('setting');

        $flat = [];

        foreach (self::SETTINGS as $key) {
            $flat[$key] = setting('Email.' . $key);
        }

        $mailer = LegacyConfigMapper::toMailer($flat);

        if ($this->transport === null || $mailer !== $this->mailer) {
            $class = config('Mailer')->transports[$mailer['transport']];

            $this->mailer    = $mailer;
            $this->transport = new $class($mailer);
        }

        return $this->transport;
    }
}
