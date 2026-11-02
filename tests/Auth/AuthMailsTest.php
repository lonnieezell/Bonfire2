<?php

declare(strict_types=1);

namespace Tests\Auth;

use Bonfire\Auth\Mails\ActivationMail;
use Bonfire\Auth\Mails\AuthCodeMail;
use Bonfire\Auth\Mails\TwoFactorMail;
use Bonfire\Users\User;
use CodeIgniter\Config\Factories;
use CodeIgniter\Database\Query;
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
        $this->useExistingInstallViews();
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
        $this->useExistingInstallViews();
        setting('Auth.views', [$viewKey => '\Tests\Support\Views\auth_mail_probe'] + config('Auth')->views);

        $this->assertSame('654321', json_decode((string) $this->mail($class)->render()->htmlBody, true)['code']);
    }

    /**
     * @param class-string<AuthCodeMail> $class
     */
    #[DataProvider('mails')]
    public function testPreviewReadsItsViewsFromConfigAndWritesNothing(string $class): void
    {
        Services::injectMock('settings', new class (config('Settings')) extends Settings {
            public function get(string $key, ?string $context = null): mixed
            {
                if ($key === 'Auth.views') {
                    throw new RuntimeException('Auth.views was read from settings while rendering a preview');
                }

                return parent::get($key, $context);
            }
        });

        $writes  = 0;
        $counter = static function (Query $query) use (&$writes): void {
            if ($query->isWriteType()) {
                $writes++;
            }
        };
        Events::on('DBQuery', $counter);

        $previewer = new MailablePreviewer(['Bonfire\Auth\Mails' => dirname(__DIR__, 2) . '/src/Auth/Mails']);
        $preview   = $previewer->render($class);

        Events::removeListener('DBQuery', $counter);

        $this->assertNull($preview->error);
        $this->assertSame(0, $writes);
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
    public function testMarkdownRenderingLeavesTheSharedRendererAsItFoundIt(string $class): void
    {
        $renderer = service('renderer');
        $renderer->resetData()->renderString('', null, false);
        $renderer->setVar('pageTitle', 'Keep me')->renderString('', null, true);

        $this->mail($class)->render();

        $this->assertSame(['pageTitle' => 'Keep me'], $renderer->getData());
        $this->assertTrue(config('View')->saveData);

        $renderer->resetData();
    }

    /**
     * @param class-string<AuthCodeMail> $class
     */
    #[DataProvider('mails')]
    public function testHostileRequestDetailsStayLiteralInTheMarkdownVersion(string $class): void
    {
        $agent = "[Reset here](https://evil.example) <mail-button url=\"https://evil.example\">Go</mail-button> `x` <img src=x> a&b https://evil.example\n\n# Fake";

        $email = $this->mail($class, $agent, '**boss**')->render();

        $this->assertStringNotContainsString('href="https://evil.example', (string) $email->htmlBody);
        $this->assertStringNotContainsString('<img', (string) $email->htmlBody);
        $this->assertStringNotContainsString('<strong>boss</strong>', (string) $email->htmlBody);
        $this->assertStringNotContainsString('>Fake</h1>', (string) $email->htmlBody);
        $this->assertStringContainsString('a&amp;b', (string) $email->htmlBody);
        $this->assertStringContainsString('a&b', (string) $email->textBody);
    }

    /**
     * @param class-string<AuthCodeMail> $class
     */
    #[DataProvider('mails')]
    public function testAnEmptyRequestDetailIsLeftOut(string $class): void
    {
        $email = $this->mail($class, '')->render();

        $this->assertStringNotContainsString('`', (string) $email->htmlBody);
        $this->assertStringNotContainsString(lang('Auth.emailDevice'), (string) $email->htmlBody);
        $this->assertStringNotContainsString(lang('Auth.emailDevice'), (string) $email->textBody);
        $this->assertStringContainsString(lang('Auth.emailIpAddress'), (string) $email->htmlBody);
    }

    /**
     * A client can send any bytes as its user agent, and CommonMark throws on
     * invalid UTF-8, which would stop the user getting their code.
     *
     * @param class-string<AuthCodeMail> $class
     */
    #[DataProvider('mails')]
    public function testAUserAgentThatIsNotUtf8StillRenders(string $class): void
    {
        $email = $this->mail($class, "caf\xE9")->render();

        $this->assertStringContainsString('654321', (string) $email->htmlBody);
        $this->assertTrue(mb_check_encoding((string) $email->textBody, 'UTF-8'));
    }

    /**
     * @param class-string<AuthCodeMail> $class
     */
    #[DataProvider('mails')]
    public function testANewInstallSendsTheBrandedMarkdownVersion(string $class): void
    {
        setting('Site.siteName', 'Acme Inc');
        setting('Site.adminAccent', '#123abc');

        $email = $this->mail($class)->render();

        $this->assertStringContainsString('Acme Inc', (string) $email->htmlBody);
        $this->assertStringContainsString('#123abc', (string) $email->htmlBody);
        $this->assertMatchesRegularExpression('/<h1[^>]*>654321<\/h1>/', (string) $email->htmlBody);
        $this->assertStringNotContainsString('mail-panel', (string) $email->htmlBody);
        $this->assertStringContainsString('654321', (string) $email->textBody);
        $this->assertStringContainsString('freddy101', (string) $email->textBody);
        $this->assertStringNotContainsString('mail-panel', (string) $email->textBody);
        $this->assertStringNotContainsString('DEBUG-VIEW', (string) $email->htmlBody);
        $this->assertStringNotContainsString('DEBUG-VIEW', (string) $email->textBody);
    }

    /**
     * @param class-string<AuthCodeMail> $class
     */
    #[DataProvider('mails')]
    public function testAnInstallCanReplaceTheMarkdownLayout(string $class): void
    {
        service('autoloader')->addNamespace('Tests\Support\Views', SUPPORTPATH . 'Views');
        config('Auth')->views['action_email_markdown_layout'] = '\Tests\Support\Views\mail_layout_probe';

        $this->assertStringContainsString('data-layout-probe', (string) $this->mail($class)->render()->htmlBody);
    }

    /**
     * @param class-string<AuthCodeMail> $class
     */
    #[DataProvider('mails')]
    public function testAnExistingInstallKeepsItsHtmlViewAndSendsNoTextPart(string $class): void
    {
        $this->useExistingInstallViews();

        $email = $this->mail($class)->render();

        $this->assertStringContainsString('654321', (string) $email->htmlBody);
        $this->assertStringNotContainsString(setting('Site.adminAccent'), (string) $email->htmlBody);
        $this->assertNull($email->textBody);
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
     * An install's published Auth config has the $views it was installed with,
     * so one from before the Markdown versions has none of their keys.
     */
    private function useExistingInstallViews(): void
    {
        unset(
            config('Auth')->views['action_email_activate_markdown'],
            config('Auth')->views['action_email_2fa_markdown'],
        );
    }

    /**
     * @param class-string<AuthCodeMail> $class
     */
    private function mail(string $class, string $userAgent = 'Test Agent', string $username = 'freddy101'): AuthCodeMail
    {
        $user = new User(['username' => $username, 'email' => 'fred@example.com']);

        return new $class($user, '654321', '10.0.0.9', $userAgent, '2026-11-02 10:00:00');
    }
}
