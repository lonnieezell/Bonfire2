<?php

declare(strict_types=1);

namespace Tests\Support\Email;

use CodeIgniter\Config\Factories;
use Config\Services;
use Myth\Postal\Config\Mailer as PostalMailer;

trait UsesAppMailers
{
    /**
     * Makes these the app's own Mailer config, so the legacy bridge steps aside.
     *
     * @param array<string, mixed> $mailers
     */
    private function useAppMailers(array $mailers, string $default = 'null'): void
    {
        $config                          = new class () extends PostalMailer {};
        $config->mailers                 = $mailers;
        $config->default                 = $default;
        $config->transports['recording'] = RecordingTransport::class;
        $config->transports['failing']   = FailingTransport::class;

        Factories::injectMock('config', 'Mailer', $config);
        Services::resetSingle('mailer');
    }
}
