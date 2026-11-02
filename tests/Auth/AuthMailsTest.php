<?php

declare(strict_types=1);

namespace Tests\Auth;

use Bonfire\Auth\Mails\ActivationMail;
use Bonfire\Auth\Mails\AuthCodeMail;
use Bonfire\Auth\Mails\TwoFactorMail;
use Bonfire\Users\User;
use CodeIgniter\Config\Factories;
use CodeIgniter\Events\Events;
use CodeIgniter\Settings\Settings;
use Config\Services;
use Myth\Postal\Mailer;
use Myth\Postal\Preview\MailablePreviewer;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class AuthMailsTest extends TestCase
{
    protected $refresh = true;

    protected function tearDown(): void
    {
        Services::resetSingle('mailer');
        Services::resetSingle('settings');
        Factories::reset('config');

        parent::tearDown();
    }

    /**
     * @param class-string<AuthCodeMail> $class
     */
    #[DataProvider('mails')]
    public function testItRendersTheAuthViewsViewWithTheActionData(string $class, string $viewKey, string $subject): void
    {
        service('autoloader')->addNamespace('Tests\Support\Views', SUPPORTPATH . 'Views');
        config('Auth')->views[$viewKey] = '\Tests\Support\Views\auth_mail_probe';

        $email = $this->mail($class)->render();

        $this->assertSame(
            ['code' => '654321', 'username' => 'freddy101', 'ipAddress' => '10.0.0.9', 'userAgent' => 'Test Agent', 'date' => '2026-11-02 10:00:00'],
            json_decode((string) $email->htmlBody, true),
        );
        $this->assertSame('fred@example.com', $email->to[0]->email);
        $this->assertSame(lang($subject), $email->subject);
        $this->assertNull($email->from);
        $this->assertSame($class, $email->mailableClass);
    }

    /**
     * @param class-string<AuthCodeMail> $class
     */
    #[DataProvider('mails')]
    public function testAViewSavedInTheSettingsTableWinsOverTheConfig(string $class, string $viewKey): void
    {
        service('autoloader')->addNamespace('Tests\Support\Views', SUPPORTPATH . 'Views');
        setting('Auth.views', [$viewKey => '\Tests\Support\Views\auth_mail_probe'] + config('Auth')->views);

        $this->assertSame('654321', json_decode((string) $this->mail($class)->render()->htmlBody, true)['code']);
    }

    /**
     * @param class-string<AuthCodeMail> $class
     */
    #[DataProvider('mails')]
    public function testPreviewRendersWithoutTouchingTheDatabase(string $class): void
    {
        Services::injectMock('settings', new class (config('Settings')) extends Settings {
            public function get(string $key, ?string $context = null): never
            {
                throw new RuntimeException('Settings were read while rendering a preview: ' . $key);
            }
        });

        $queries = 0;
        $counter = static function () use (&$queries): void {
            $queries++;
        };
        Events::on('DBQuery', $counter);

        $previewer = new MailablePreviewer(['Bonfire\Auth\Mails' => dirname(__DIR__, 2) . '/src/Auth/Mails']);
        $preview   = $previewer->render($class);

        Events::removeListener('DBQuery', $counter);

        $this->assertNull($preview->error);
        $this->assertSame(0, $queries);
        $this->assertNotEmpty($preview->htmlBody);
        $this->assertNotEmpty($preview->subject);
    }

    /**
     * @param class-string<AuthCodeMail> $class
     */
    #[DataProvider('mails')]
    public function testItFailsInsteadOfSendingWithoutAFrom(string $class): void
    {
        $sent = Mailer::fake();
        setting('Email.fromEmail', '');

        $result = $this->mail($class)->send();

        $this->assertFalse($result->success);
        $sent->assertSentCount(0);
    }

    /**
     * @param class-string<AuthCodeMail> $class
     */
    #[DataProvider('mails')]
    public function testRenderingDoesNotLeaveTheCodeInTheSharedRenderer(string $class): void
    {
        $this->mail($class)->render();

        $this->assertArrayNotHasKey('code', service('renderer')->getData());
    }

    /**
     * @return array<string, array{class-string<AuthCodeMail>, string, string}>
     */
    public static function mails(): iterable
    {
        return [
            'activation' => [ActivationMail::class, 'action_email_activate_email', 'Auth.emailActivateSubject'],
            'two-factor' => [TwoFactorMail::class, 'action_email_2fa_email', 'Auth.email2FASubject'],
        ];
    }

    public function testPreviewsDiscoverBothMailsAndNotTheirBaseClass(): void
    {
        $previewer = new MailablePreviewer(['Bonfire\Auth\Mails' => dirname(__DIR__, 2) . '/src/Auth/Mails']);

        $this->assertSame([ActivationMail::class, TwoFactorMail::class], $previewer->discover());
    }

    /**
     * @param class-string<AuthCodeMail> $class
     */
    private function mail(string $class): AuthCodeMail
    {
        $user = new User(['username' => 'freddy101', 'email' => 'fred@example.com']);

        return new $class($user, '654321', '10.0.0.9', 'Test Agent', '2026-11-02 10:00:00');
    }
}
