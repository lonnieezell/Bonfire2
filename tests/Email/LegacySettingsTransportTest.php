<?php

declare(strict_types=1);

namespace Tests\Email;

use Bonfire\Email\LegacySettingsTransport;
use CodeIgniter\Config\Factories;
use CodeIgniter\Events\Events;
use CodeIgniter\Test\TestLogger;
use Config\Email as EmailConfig;
use Config\Services;
use Myth\Postal\Config\Mailer as PostalMailer;
use Myth\Postal\Mailable;
use Myth\Postal\SendResult;
use Tests\Support\Email\RecordingTransport;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class LegacySettingsTransportTest extends TestCase
{
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();

        RecordingTransport::reset();
        $this->resetSharedState();
    }

    protected function tearDown(): void
    {
        $this->resetSharedState();

        parent::tearDown();
    }

    /**
     * The mailer service and the Mailer config it holds outlive a test, and the
     * settings service caches what it read from the database.
     */
    private function resetSharedState(): void
    {
        Services::resetSingle('mailer');
        Services::resetSingle('settings');
        Factories::reset('config');
    }

    public function testBootRegistersTheBridgeOnWebAndSparkEvents(): void
    {
        foreach (['pre_system', 'pre_command'] as $event) {
            Factories::reset('config');

            Events::trigger($event);

            $config = config('Mailer');

            $this->assertSame(PostalMailer::class, $config::class);
            $this->assertSame('legacy', $config->default, $event);
            $this->assertSame(['transport' => 'bonfire-legacy'], $config->mailers['legacy'], $event);
            $this->assertSame(LegacySettingsTransport::class, $config->transports['bonfire-legacy'], $event);
        }
    }

    public function testMailableSendsThroughSmtpBuiltFromSavedSettings(): void
    {
        setting('Email.protocol', 'smtp');
        setting('Email.SMTPHost', 'smtp.example.com');
        setting('Email.SMTPPort', '587');
        setting('Email.SMTPUser', 'mailer');
        setting('Email.SMTPPass', 'secret');

        $result = $this->sendMailable('smtp');

        $this->assertTrue($result->success);
        $this->assertCount(1, RecordingTransport::$sent);

        $settings = RecordingTransport::$sent[0]['settings'];
        $this->assertSame('smtp', $settings['transport']);
        $this->assertSame('smtp.example.com', $settings['host']);
        $this->assertSame(587, $settings['port']);
        $this->assertSame('mailer', $settings['username']);
        $this->assertSame('secret', $settings['password']);
        $this->assertSame(['user@example.com'], array_map(static fn ($a) => $a->email, RecordingTransport::$sent[0]['email']->to));
    }

    public function testMailableSendsThroughSendmailBuiltFromSavedSettings(): void
    {
        setting('Email.protocol', 'sendmail');
        setting('Email.mailPath', '/opt/bin/sendmail');

        $this->sendMailable('sendmail');

        $this->assertSame(
            ['transport' => 'sendmail', 'path' => '/opt/bin/sendmail'],
            RecordingTransport::$sent[0]['settings'],
        );
    }

    public function testMailableSendsThroughMailBuiltFromSavedSettings(): void
    {
        setting('Email.protocol', 'mail');

        $this->sendMailable('mail');

        $this->assertSame(['transport' => 'mail'], RecordingTransport::$sent[0]['settings']);
    }

    public function testWithoutSavedSettingsItSendsThroughMail(): void
    {
        $this->sendMailable('mail');

        $this->assertSame(['transport' => 'mail'], RecordingTransport::$sent[0]['settings']);
    }

    public function testConfigEmailValuesAreUsedWhenNothingIsSaved(): void
    {
        $config           = config(EmailConfig::class);
        $config->protocol = 'sendmail';
        $config->mailPath = '/from/config/sendmail';

        $this->sendMailable('sendmail');

        $this->assertSame('/from/config/sendmail', RecordingTransport::$sent[0]['settings']['path']);
    }

    public function testSavedSettingsWinOverConfigEmail(): void
    {
        $config           = config(EmailConfig::class);
        $config->protocol = 'sendmail';
        $config->mailPath = '/from/config/sendmail';

        setting('Email.mailPath', '/from/database/sendmail');

        $this->sendMailable('sendmail');

        $this->assertSame('/from/database/sendmail', RecordingTransport::$sent[0]['settings']['path']);
    }

    public function testEnvValuesReachTheTransportThroughConfigEmail(): void
    {
        $_SERVER['email.protocol'] = 'sendmail';
        $_SERVER['email.mailPath'] = '/from/env/sendmail';

        try {
            $this->sendMailable('sendmail');
        } finally {
            unset($_SERVER['email.protocol'], $_SERVER['email.mailPath']);
        }

        $this->assertSame('/from/env/sendmail', RecordingTransport::$sent[0]['settings']['path']);
    }

    public function testItBuildsTheRealTransportTheSettingsDescribe(): void
    {
        $transport = new LegacySettingsTransport();

        // SendmailTransport::ping() checks the path it was built with is executable.
        setting('Email.protocol', 'sendmail');
        setting('Email.mailPath', PHP_BINARY);
        $this->assertTrue($transport->ping());

        setting('Email.mailPath', '/no/such/sendmail');
        $this->assertFalse($transport->ping());
    }

    public function testSettingsChangedAfterBootArePickedUpByTheNextSend(): void
    {
        setting('Email.protocol', 'sendmail');
        setting('Email.mailPath', '/first/sendmail');

        $this->sendMailable('sendmail');

        setting('Email.mailPath', '/second/sendmail');

        $this->sendMailable('sendmail');

        $this->assertSame('/first/sendmail', RecordingTransport::$sent[0]['settings']['path']);
        $this->assertSame('/second/sendmail', RecordingTransport::$sent[1]['settings']['path']);
    }

    public function testUnchangedSettingsReuseTheTransportSoKeepAliveSurvives(): void
    {
        setting('Email.protocol', 'smtp');
        setting('Email.SMTPHost', 'smtp.example.com');
        setting('Email.SMTPKeepAlive', true);

        $this->sendMailable('smtp');
        $this->sendMailable('smtp');

        $this->assertCount(2, RecordingTransport::$sent);
        $this->assertCount(1, RecordingTransport::$built);
    }

    public function testFirstSendLogsOneDeprecationNotice(): void
    {
        $before = $this->deprecationNotices();

        $this->sendMailable('mail');
        $this->sendMailable('mail');

        $this->assertSame($before + 1, $this->deprecationNotices());
    }

    public function testAppMailerConfigGetsNothingRegisteredAndItsDefaultIsUsed(): void
    {
        setting('Email.protocol', 'smtp');

        $config = $this->injectAppMailer('app');

        LegacySettingsTransport::register();

        $this->assertSame('app', $config->default);
        $this->assertArrayNotHasKey('legacy', $config->mailers);
        $this->assertArrayNotHasKey('bonfire-legacy', $config->transports);

        $this->mailable()->send();

        $this->assertSame(['transport' => 'app'], RecordingTransport::$sent[0]['settings']);
    }

    public function testAppMailerConfigWithANullDefaultStaysNull(): void
    {
        setting('Email.protocol', 'smtp');

        $config = $this->injectAppMailer('null');

        LegacySettingsTransport::register();

        $this->assertSame('null', $config->default);
        $this->assertArrayNotHasKey('legacy', $config->mailers);

        $this->assertTrue($this->mailable()->send()->success);
        $this->assertSame([], RecordingTransport::$sent);
    }

    /**
     * Boots the bridge, points the named real transport at the recording double
     * and sends a Mailable through the default mailer.
     */
    private function sendMailable(string $transport): SendResult
    {
        LegacySettingsTransport::register();

        config('Mailer')->transports[$transport] = RecordingTransport::class;

        return $this->mailable()->send();
    }

    private function injectAppMailer(string $default): PostalMailer
    {
        $config = new class () extends PostalMailer {};

        $config->default           = $default;
        $config->mailers['app']    = ['transport' => 'app'];
        $config->transports['app'] = RecordingTransport::class;

        Factories::injectMock('config', 'Mailer', $config);

        return $config;
    }

    private function mailable(): Mailable
    {
        return new class () extends Mailable {
            protected function build(): void
            {
                $this->email
                    ->from('site@example.com')
                    ->to('user@example.com')
                    ->subject('Hello')
                    ->text('Hello');
            }
        };
    }

    private function deprecationNotices(): int
    {
        /** @var list<array{level: string, message: string}> $logs */
        $logs = $this->getPrivateProperty(TestLogger::class, 'op_logs');

        return count(array_filter(
            $logs,
            static fn (array $log): bool => $log['level'] === 'notice' && str_contains($log['message'], 'bonfire:email-env'),
        ));
    }
}
