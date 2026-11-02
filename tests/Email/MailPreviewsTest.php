<?php

declare(strict_types=1);

namespace Tests\Email;

use Bonfire\Auth\Mails\ActivationMail;
use Bonfire\Auth\Mails\TwoFactorMail;
use Bonfire\Users\User;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Myth\Postal\Address;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Email\RecordingTransport;
use Tests\Support\Mails\BrokenMail;
use Tests\Support\Modules\Mailing\Mails\WelcomeMail;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class MailPreviewsTest extends TestCase
{
    private const WELCOME = 'Tests.Support.Modules.Mailing.Mails.WelcomeMail';
    private const RECEIPT = 'Tests.Support.Mails.ReceiptMail';
    private const BROKEN  = 'Tests.Support.Mails.BrokenMail';

    protected $refresh = true;
    private User $admin;

    /**
     * @var array<string, string>
     */
    private array $namespaces = [];

    /**
     * @var array{array<string, array<string, mixed>>, array<string, class-string>}
     */
    private array $mailerConfig;

    protected function setUp(): void
    {
        parent::setUp();

        setting('Site.siteOnline', true);
        setting('Email.fromEmail', 'site@example.com');
        setting('Email.fromName', 'The Site');

        cache()->delete('app-modules-search');
        config('Bonfire')->appModules = ['Tests\Support\Modules' => SUPPORTPATH . 'Modules'];

        $this->namespaces                                           = config('Postal')->mailableNamespaces;
        config('Postal')->mailableNamespaces['Tests\Support\Mails'] = SUPPORTPATH . 'Mails';

        $mailer                          = config('Mailer');
        $this->mailerConfig              = [$mailer->mailers, $mailer->transports];
        $mailer->transports['recording'] = RecordingTransport::class;
        $mailer->mailers['first']        = ['transport' => 'recording', 'label' => 'first'];
        $mailer->mailers['second']       = ['transport' => 'recording', 'label' => 'second'];

        RecordingTransport::reset();

        foreach (['auth', 'bonfire', 'mailer', 'menus', 'widgets'] as $service) {
            Services::resetSingle($service);
        }

        $this->admin = $this->createUser(['email' => 'admin@example.com']);
        $this->admin->addGroup('superadmin');
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        cache()->delete('app-modules-search');
        config('Bonfire')->appModules           = [];
        config('Postal')->mailableNamespaces    = $this->namespaces;
        $mailer                                 = config('Mailer');
        [$mailer->mailers, $mailer->transports] = $this->mailerConfig;

        foreach (['auth', 'bonfire', 'mailer', 'menus', 'settings', 'widgets'] as $service) {
            Services::resetSingle($service);
        }

        parent::tearDown();
    }

    public function testListGroupsMailablesFromCoreAppAndPostalByModule(): void
    {
        $response = $this->actingAs($this->admin)->get(route_to('mail-previews'));

        $response->assertOK();

        $groups = [];

        foreach ($this->xpath($response)->query('//*[@data-module]') as $group) {
            /** @var DOMElement $group */
            foreach ($this->xpath($response)->query('//*[@data-module="' . $group->getAttribute('data-module') . '"]//*[@data-mailable]') as $row) {
                /** @var DOMElement $row */
                $groups[$group->getAttribute('data-module')][] = $row->getAttribute('data-mailable');
            }
        }

        $this->assertSame([
            'Auth'    => ['Bonfire.Auth.Mails.ActivationMail', 'Bonfire.Auth.Mails.TwoFactorMail'],
            'Mailing' => [self::WELCOME],
            'Support' => [self::BROKEN, self::RECEIPT],
        ], $groups);
    }

    public function testListShowsEachSubject(): void
    {
        $response = $this->actingAs($this->admin)->get(route_to('mail-previews'));

        $this->assertSame('Welcome aboard', $this->cellOf($response, self::WELCOME, 'subject'));
        $this->assertSame(lang('Auth.emailActivateSubject'), $this->cellOf($response, 'Bonfire.Auth.Mails.ActivationMail', 'subject'));
    }

    public function testAMailableThatThrowsShowsItsErrorWithoutBreakingTheList(): void
    {
        $response = $this->actingAs($this->admin)->get(route_to('mail-previews'));

        $response->assertOK();
        $this->assertSame(BrokenMail::ERROR, $this->cellOf($response, self::BROKEN, 'error'));
        $this->assertSame('Your receipt', $this->cellOf($response, self::RECEIPT, 'subject'));
    }

    public function testPreviewOfAMailableThatThrowsShowsItsErrorAndNoSendForm(): void
    {
        $response = $this->actingAs($this->admin)->get(route_to('mail-preview', self::BROKEN));

        $response->assertOK();
        $this->assertSame(BrokenMail::ERROR, trim($this->xpath($response)->query('//*[@data-preview-error]')->item(0)->textContent));
        $this->assertCount(0, $this->xpath($response)->query('//form[@data-send-to-me]'));
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('provideAuthMailablesPreview')]
    public function testAuthMailablesPreview(string $slug, string $class, string $subject): void
    {
        // The sample mail prints the current time, so both renders must share a clock
        Time::setTestNow('2026-01-15 12:00:00');

        $response = $this->actingAs($this->admin)->get(route_to('mail-preview', $slug));

        $response->assertOK();

        $sample = $class::previewInstance()->render();
        $panels = $this->panels($response);

        $this->assertSame($sample->htmlBody, $this->iframe($response)->getAttribute('srcdoc'));
        $this->assertStringContainsString('123456', $panels['text']);
        $this->assertStringContainsString('Subject: ' . lang($subject), $panels['raw']);
        $this->assertStringContainsString('To: preview@example.com', $panels['raw']);
    }

    /**
     * @return array<string, array{string, class-string, string}>
     */
    public static function provideAuthMailablesPreview(): iterable
    {
        return [
            'activation' => ['Bonfire.Auth.Mails.ActivationMail', ActivationMail::class, 'Auth.emailActivateSubject'],
            'two-factor' => ['Bonfire.Auth.Mails.TwoFactorMail', TwoFactorMail::class, 'Auth.email2FASubject'],
        ];
    }

    public function testHtmlIsSandboxedWithNoScriptExecution(): void
    {
        $response = $this->actingAs($this->admin)->get(route_to('mail-preview', self::WELCOME));
        $iframe   = $this->iframe($response);

        $this->assertTrue($iframe->hasAttribute('sandbox'));
        $this->assertSame('', $iframe->getAttribute('sandbox'));
        $this->assertSame(WelcomeMail::HTML, $iframe->getAttribute('srcdoc'));
        $this->assertStringNotContainsString('<script>window.parent', (string) $response->response()->getBody());
    }

    public function testPlainTextAndRawMimeTabsAreEscaped(): void
    {
        $response = $this->actingAs($this->admin)->get(route_to('mail-preview', self::WELCOME));
        $panels   = $this->panels($response);

        $this->assertSame('Welcome, in plain text', trim($panels['text']));
        $this->assertStringContainsString('Subject: Welcome aboard', $panels['raw']);
        $this->assertStringNotContainsString('<h1>Welcome</h1>', (string) $response->response()->getBody());
    }

    public function testAMailWithoutHtmlOpensOnThePlainTextTab(): void
    {
        $response = $this->actingAs($this->admin)->get(route_to('mail-preview', self::RECEIPT));

        $this->assertCount(0, $this->xpath($response)->query('//iframe'));
        $this->assertStringContainsString("tab: 'text'", (string) $response->response()->getBody());
    }

    public function testSendToMeDeliversOnlyToTheSignedInAdminThroughTheChosenMailer(): void
    {
        $response = $this->send(self::WELCOME, ['mailer' => 'second', 'to' => 'attacker@example.com', 'cc' => 'attacker@example.com']);

        $response->assertRedirectTo(route_to('mail-preview', self::WELCOME));
        $this->assertCount(1, RecordingTransport::$sent);

        ['settings' => $settings, 'email' => $email] = RecordingTransport::$sent[0];

        $this->assertSame('second', $settings['label']);
        $this->assertSame(['admin@example.com'], $this->addresses($email->to));
        $this->assertSame([], $email->cc);
        $this->assertSame([], $email->bcc);
        $this->assertSame(['X-Campaign' => 'welcome'], $email->headers);
        $this->assertNull($email->returnPath);
        $this->assertSame('site@example.com', $email->from?->email);
        $this->assertSame(WelcomeMail::class, $email->mailableClass);
    }

    public function testThePermissionsCommandGrantsPreviewsToAnInstallThatLacksThem(): void
    {
        $user = $this->createUser();
        $user->addGroup('admin');

        $matrix = config('AuthGroups')->matrix;

        foreach (['superadmin', 'admin'] as $group) {
            $matrix[$group] = array_values(array_diff($matrix[$group], ['email.*', 'email.preview']));
        }

        setting('AuthGroups.matrix', $matrix);
        setting('AuthGroups.appliedGrants', []);

        $this->assertFalse($user->can('email.preview'));
        $this->assertFalse($this->admin->can('email.preview'));

        command('bonfire:permissions');

        $this->assertTrue($user->can('email.preview'));
        $this->assertTrue($this->admin->can('email.preview'));
    }

    public function testSendFormOffersEachMailerAndHasNoRecipientField(): void
    {
        $response = $this->actingAs($this->admin)->get(route_to('mail-preview', self::WELCOME));
        $xpath    = $this->xpath($response);
        $form     = $xpath->query('//form[@data-send-to-me]')->item(0);

        $this->assertSame(url_to('mail-preview-send', self::WELCOME), $form->getAttribute('action'));
        $this->assertSame('post', $form->getAttribute('method'));

        $options = [];

        foreach ($xpath->query('.//select[@name="mailer"]/option', $form) as $option) {
            $options[] = $option->getAttribute('value');
        }

        $this->assertContains('first', $options);
        $this->assertContains('second', $options);
        // Without an app Mailer config, the legacy bridge is the default.
        $this->assertSame('legacy', $xpath->query('.//select/option[@selected]', $form)->item(0)->getAttribute('value'));

        $names = [];

        foreach ($xpath->query('.//input | .//select | .//textarea', $form) as $field) {
            $names[] = $field->getAttribute('name');
        }

        $this->assertSame([csrf_token(), 'mailer'], $names);
    }

    public function testAnUnknownMailerSendsNothing(): void
    {
        $this->send(self::WELCOME, ['mailer' => 'nope'])->assertRedirect();

        $this->assertSame([], RecordingTransport::$sent);
    }

    public function testSendingWithoutAFromFailsInsteadOfGoingOut(): void
    {
        setting('Email.fromEmail', '');

        $this->send(self::WELCOME, ['mailer' => 'second'])->assertRedirect();

        $this->assertSame([], RecordingTransport::$sent);
    }

    public function testSendingAMailableThatThrowsRedirectsWithoutSending(): void
    {
        $this->send(self::BROKEN, ['mailer' => 'second'])->assertRedirect();

        $this->assertSame([], RecordingTransport::$sent);
    }

    public function testSendWithoutACsrfTokenSendsNothing(): void
    {
        $this->actingAs($this->admin)->post(route_to('mail-preview-send', self::WELCOME), ['mailer' => 'second']);

        $this->assertSame([], RecordingTransport::$sent);
    }

    public function testAMailableOutsideTheMapIsNotFound(): void
    {
        config('Postal')->mailableNamespaces = [];

        $this->expectException(PageNotFoundException::class);

        $this->actingAs($this->admin)->get(route_to('mail-preview', self::RECEIPT));
    }

    public function testAnUnknownClassIsNotFound(): void
    {
        $this->expectException(PageNotFoundException::class);

        $this->actingAs($this->admin)->get(route_to('mail-preview', 'App.Models.Nope'));
    }

    /**
     * @param 'index'|'send'|'show' $route
     */
    #[DataProvider('provideUsersWithoutThePermissionGetNotFound')]
    public function testUsersWithoutThePermissionGetNotFound(string $route): void
    {
        $user = $this->createUser();
        $user->addGroup('developer');

        $this->expectException(PageNotFoundException::class);

        match ($route) {
            'index' => $this->actingAs($user)->get(route_to('mail-previews')),
            'show'  => $this->actingAs($user)->get(route_to('mail-preview', self::WELCOME)),
            'send'  => $this->actingAs($user)->post(route_to('mail-preview-send', self::WELCOME), [csrf_token() => csrf_hash(), 'mailer' => 'second']),
        };
    }

    /**
     * @return array<string, array{string}>
     */
    public static function provideUsersWithoutThePermissionGetNotFound(): iterable
    {
        return [
            'index' => ['index'],
            'show'  => ['show'],
            'send'  => ['send'],
        ];
    }

    public function testUsersWithoutThePermissionSeeNoMenuItem(): void
    {
        $user = $this->createUser();
        $user->addGroup('developer');

        $body = (string) $this->actingAs($user)->get(ADMIN_AREA)->response()->getBody();

        $this->assertStringNotContainsString(url_to('mail-previews'), $body);
    }

    #[DataProvider('provideGroupsGrantedThePermissionSeeTheMenuItemAndTheList')]
    public function testGroupsGrantedThePermissionSeeTheMenuItemAndTheList(string $group): void
    {
        $user = $this->createUser();
        $user->addGroup($group);

        $body = (string) $this->actingAs($user)->get(ADMIN_AREA)->response()->getBody();

        $this->assertStringContainsString('href="' . url_to('mail-previews') . '"', $body);

        Services::resetSingle('bonfire');
        Services::resetSingle('menus');
        Services::resetSingle('widgets');

        $this->actingAs($user)->get(route_to('mail-previews'))->assertOK();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function provideGroupsGrantedThePermissionSeeTheMenuItemAndTheList(): iterable
    {
        return [
            'superadmin' => ['superadmin'],
            'admin'      => ['admin'],
        ];
    }

    public function testPostalsOwnPreviewRouteStaysOff(): void
    {
        $this->assertFalse(config('Postal')->previewEnabled('development'));
    }

    /**
     * @param array<string, string> $params
     */
    private function send(string $slug, array $params): TestResponse
    {
        return $this->actingAs($this->admin)->post(route_to('mail-preview-send', $slug), [csrf_token() => csrf_hash()] + $params);
    }

    private function xpath(TestResponse $response): DOMXPath
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?>' . $response->response()->getBody());
        libxml_clear_errors();

        return new DOMXPath($dom);
    }

    private function cellOf(TestResponse $response, string $slug, string $cell): string
    {
        return trim($this->xpath($response)->query('//*[@data-mailable="' . $slug . '"]//*[@data-' . $cell . ']')->item(0)->textContent);
    }

    private function iframe(TestResponse $response): DOMElement
    {
        $iframe = $this->xpath($response)->query('//iframe')->item(0);
        $this->assertInstanceOf(DOMElement::class, $iframe);

        return $iframe;
    }

    /**
     * @return array{text: string, raw: string}
     */
    private function panels(TestResponse $response): array
    {
        $xpath = $this->xpath($response);

        return [
            'text' => $xpath->query('//*[@data-tab-panel="text"]')->item(0)->textContent,
            'raw'  => $xpath->query('//*[@data-tab-panel="raw"]')->item(0)->textContent,
        ];
    }

    /**
     * @param list<Address> $addresses
     *
     * @return list<string>
     */
    private function addresses(array $addresses): array
    {
        return array_map(static fn ($address) => $address->email, $addresses);
    }
}
