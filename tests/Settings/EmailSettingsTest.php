<?php

namespace Tests\Settings;

use Bonfire\Users\User;
use CodeIgniter\Config\Factories;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use DOMDocument;
use DOMXPath;
use Myth\Postal\Config\Mailer as PostalMailer;
use Tests\Support\Email\FailingTransport;
use Tests\Support\Email\RecordingTransport;
use Tests\Support\SubmitsForms;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class EmailSettingsTest extends TestCase
{
    use SubmitsForms;

    protected $refresh = true;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        RecordingTransport::reset();

        $this->user = $this->createUser(['email' => 'admin@example.com']);
        $this->user->addGroup('superadmin');
    }

    protected function tearDown(): void
    {
        Factories::reset('config');

        foreach (['mailer', 'menus', 'settings'] as $service) {
            Services::resetSingle($service);
        }

        parent::tearDown();
    }

    public function testFromNameAndAddressRoundTripThroughTheForm(): void
    {
        setting('Email.fromName', 'Ada');
        setting('Email.fromEmail', 'ada@example.com');

        $html   = $this->page();
        $fields = $this->formFields($html, site_url(ADMIN_AREA . '/settings/email'));

        $this->assertSame(['fromEmail', 'fromName'], $this->sortedKeysWithoutCsrf($fields));

        $fields['fromName'] = 'Grace';

        $this->actingAs($this->user)->post('/admin/settings/email', $fields)->assertSessionMissing('errors');

        $this->assertSame('Grace', setting('Email.fromName'));
        $this->assertSame('ada@example.com', setting('Email.fromEmail'));
    }

    public function testFromIsValidated(): void
    {
        setting('Email.fromEmail', 'ada@example.com');

        $this->actingAs($this->user)->post('/admin/settings/email', [
            'fromName'  => 'A',
            'fromEmail' => 'nope',
        ])->assertSessionHas('errors');

        $this->assertSame('ada@example.com', setting('Email.fromEmail'));
    }

    public function testSavingIgnoresTransportFields(): void
    {
        $this->actingAs($this->user)->post('/admin/settings/email', [
            'fromName'  => 'Ada',
            'fromEmail' => 'ada@example.com',
            'protocol'  => 'smtp',
            'SMTPHost'  => 'evil.example.com',
            'SMTPPass'  => 'hunter2',
        ])->assertSessionMissing('errors');

        $this->assertNotSame('evil.example.com', setting('Email.SMTPHost'));
        $this->assertNotSame('hunter2', setting('Email.SMTPPass'));
    }

    public function testListsMailersAndMarksTheDefault(): void
    {
        $this->useAppMailers([
            'main'   => ['transport' => 'recording', 'host' => 'smtp.example.com'],
            'backup' => ['transport' => 'log'],
        ], 'main');

        $xpath = $this->xpath($this->page());

        $this->assertCount(2, $xpath->query('//*[@data-mailer]'));
        $this->assertCount(1, $xpath->query('//*[@data-mailer="main"]//*[@data-default-mailer]'));
        $this->assertCount(0, $xpath->query('//*[@data-mailer="backup"]//*[@data-default-mailer]'));
        $this->assertCount(1, $xpath->query('//*[@data-mailer="main"]//*[@data-setting="host" and contains(., "smtp.example.com")]'));
    }

    public function testNoSecretReachesTheHtml(): void
    {
        $secrets = ['hunter2-password', 'abc-secret', 'def-token', 'ghi-apikey', 'jkl-dkim', 'mno-custom', 'pqr-url'];

        $this->useAppMailers([
            'smtp' => ['transport' => 'recording', 'password' => $secrets[0]],
            'ses'  => ['transport' => 'recording', 'credentials' => ['secret' => $secrets[1]], 'nested' => ['token' => $secrets[2]]],
            'dkim' => ['transport' => 'recording', 'key' => $secrets[3], 'dkim' => ['privateKey' => $secrets[4]]],
            'mine' => ['transport' => 'unregistered', 'clientSecret' => $secrets[5]],
            'dsn'  => ['transport' => 'recording', 'dsn' => "smtp://user:{$secrets[6]}@mail.example.com"],
        ], 'smtp');

        // The old page printed the stored password. So must nothing here.
        setting('Email.SMTPPass', 'stored-row-password');

        $html = $this->page();

        foreach ([...$secrets, 'stored-row-password'] as $secret) {
            $this->assertStringNotContainsString($secret, $html);
        }
    }

    public function testBridgedMailerShowsResolvedAndMaskedUnderTheDeprecationWarning(): void
    {
        setting('Email.protocol', 'smtp');
        setting('Email.SMTPHost', 'smtp.example.com');
        setting('Email.SMTPUser', 'mailer');
        setting('Email.SMTPPass', 'stored-row-password');

        $html  = $this->page();
        $xpath = $this->xpath($html);

        $this->assertStringNotContainsString('stored-row-password', $html);
        $this->assertCount(1, $xpath->query('//*[@data-warning="bridge"]'));
        $this->assertCount(0, $xpath->query('//*[@data-warning="leftoverRows"]'));
        $this->assertCount(1, $xpath->query('//*[@data-mailer="legacy"]//*[@data-default-mailer]'));
        $this->assertCount(1, $xpath->query('//*[@data-mailer="legacy"]//*[@data-setting="host" and contains(., "smtp.example.com")]'));
    }

    public function testShowsAWarningForEachProblem(): void
    {
        $this->useAppMailers(['null' => ['transport' => 'null']], 'null');
        setting('Email.SMTPPass', 'hunter2');

        $xpath = $this->xpath($this->page());

        $this->assertCount(1, $xpath->query('//*[@data-warning="leftoverRows"]'));
        $this->assertCount(1, $xpath->query('//*[@data-warning="nullDefault"]'));
        $this->assertCount(0, $xpath->query('//*[@data-warning="bridge"]'));
    }

    public function testNoWarningsWhenAllIsWell(): void
    {
        $this->useAppMailers(['main' => ['transport' => 'recording']], 'main');

        $this->assertCount(0, $this->xpath($this->page())->query('//*[@data-warning]'));
    }

    public function testRendersABrokenConfig(): void
    {
        $this->useAppMailers([
            'odd'  => ['transport' => 'nope'],
            'a'    => ['transport' => 'failover', 'chain' => ['b']],
            'b'    => ['transport' => 'failover', 'chain' => ['a']],
            'both' => ['transport' => 'failover', 'chain' => ['odd', 'gone']],
        ], 'gone');

        $xpath = $this->xpath($this->page());

        $this->assertCount(1, $xpath->query('//*[@data-warning="undefinedDefault"]'));
        $this->assertCount(1, $xpath->query('//*[@data-mailer="odd"]//*[@data-problem="unknownTransport"]'));
        $this->assertCount(1, $xpath->query('//*[@data-mailer="a"]//*[@data-problem="loops"]'));
    }

    public function testFailoverChainsLinkToDefinedChildrenOnly(): void
    {
        $this->useAppMailers([
            'both' => ['transport' => 'failover', 'chain' => ['main', 'gone']],
            'main' => ['transport' => 'recording'],
        ], 'both');

        $xpath = $this->xpath($this->page());

        $this->assertSame('#mailer-main', $xpath->query('//*[@data-mailer="both"]//a[@data-chain-child="main"]')->item(0)?->getAttribute('href'));
        $this->assertCount(1, $xpath->query('//*[@id="mailer-main"][@data-mailer="main"]'));
        $this->assertCount(0, $xpath->query('//*[@data-mailer="both"]//a[@data-chain-child="gone"]'));
        $this->assertCount(1, $xpath->query('//*[@data-mailer="both"]//*[@data-chain-missing="gone"]'));
    }

    public function testTestSendDefaultsToTheAdminsAddressAndTheDefaultMailer(): void
    {
        $this->useAppMailers(['first' => ['transport' => 'recording'], 'second' => ['transport' => 'recording']], 'second');

        $xpath = $this->xpath($this->page());

        $this->assertSame('admin@example.com', $xpath->query('//input[@name="recipient"]')->item(0)?->getAttribute('value'));
        $this->assertSame('second', $xpath->query('//select[@name="mailer"]/option[@selected]')->item(0)?->getAttribute('value'));
    }

    public function testTestSendGoesThroughTheChosenMailerAndReportsSuccess(): void
    {
        $this->useAppMailers([
            'first'  => ['transport' => 'recording', 'label' => 'first'],
            'second' => ['transport' => 'recording', 'label' => 'second'],
        ], 'first');
        setting('Email.fromEmail', 'site@example.com');

        $response = $this->sendTestMail('second', 'someone@example.com');

        $response->assertRedirectTo(url_to('email-settings'));
        $this->assertCount(1, RecordingTransport::$sent);
        $this->assertSame('second', RecordingTransport::$sent[0]['settings']['label']);

        $xpath = $this->xpath($this->pageAfterRedirect());
        $this->assertCount(1, $xpath->query('//*[@data-test-result="sent"]'));
        // The form comes back as it was submitted.
        $this->assertSame('someone@example.com', $xpath->query('//input[@name="recipient"]')->item(0)?->getAttribute('value'));
        $this->assertSame('second', $xpath->query('//select[@name="mailer"]/option[@selected]')->item(0)?->getAttribute('value'));
    }

    public function testTestSendSaysWhenAMailerDidNotDeliver(): void
    {
        $this->useAppMailers(['off' => ['transport' => 'null']], 'off');
        setting('Email.fromEmail', 'site@example.com');

        $this->sendTestMail('off', 'someone@example.com');

        $this->assertCount(1, $this->xpath($this->pageAfterRedirect())->query('//*[@data-test-result="discarded"]'));
    }

    public function testTestSendShowsTheErrorInlineWithoutSecrets(): void
    {
        $this->useAppMailers(['main' => ['transport' => 'failing', 'password' => 'hunter2', 'error' => 'Refused <b>hunter2</b>']], 'main');
        setting('Email.fromEmail', 'site@example.com');

        $this->sendTestMail('main', 'someone@example.com');

        $result = $this->xpath($this->pageAfterRedirect())->query('//*[@data-test-result="failed"]')->item(0);

        // Parsed back to the same text, so the markup in the error was escaped.
        $this->assertSame('Refused <b>***</b>', $result?->textContent);
    }

    public function testTestSendValidatesTheRecipient(): void
    {
        $this->useAppMailers(['main' => ['transport' => 'recording']], 'main');
        setting('Email.fromEmail', 'site@example.com');

        $this->sendTestMail('main', 'a@example.com, b@example.com')->assertSessionHas('errors');

        $this->assertSame([], RecordingTransport::$sent);
    }

    public function testTestSendRefusesAnUnknownMailer(): void
    {
        $this->useAppMailers(['main' => ['transport' => 'recording']], 'main');
        setting('Email.fromEmail', 'site@example.com');

        $this->sendTestMail('gone', 'someone@example.com');

        $this->assertSame([], RecordingTransport::$sent);
        $this->assertCount(1, $this->xpath($this->pageAfterRedirect())->query('//*[@data-test-result="failed"]'));
    }

    public function testTestSendNeedsAdminSettingsPermission(): void
    {
        $this->useAppMailers(['main' => ['transport' => 'recording']], 'main');
        setting('Email.fromEmail', 'site@example.com');

        $user = $this->createUser();
        $user->addGroup('admin');

        $this->actingAs($user)->post(route_to('email-test-send'), [
            csrf_token() => csrf_hash(),
            'mailer'     => 'main',
            'recipient'  => 'someone@example.com',
        ])->assertRedirectTo(ADMIN_AREA);

        $this->assertSame([], RecordingTransport::$sent);
    }

    public function testTestSendWithoutACsrfTokenSendsNothing(): void
    {
        $this->useAppMailers(['main' => ['transport' => 'recording']], 'main');
        setting('Email.fromEmail', 'site@example.com');

        $this->actingAs($this->user)->post(route_to('email-test-send'), [
            'mailer'    => 'main',
            'recipient' => 'someone@example.com',
        ]);

        $this->assertSame([], RecordingTransport::$sent);
    }

    public function testTestSendIsPostOnly(): void
    {
        $this->expectException(PageNotFoundException::class);

        $this->actingAs($this->user)->get('/admin/settings/email/test');
    }

    public function testPageNeedsAdminSettingsPermission(): void
    {
        // The admin group may reach the admin area but not change settings
        $user = $this->createUser();
        $user->addGroup('admin');

        $this->actingAs($user)->get('/admin/settings/email')->assertRedirectTo(ADMIN_AREA);
    }

    public function testSaveNeedsAdminSettingsPermission(): void
    {
        $user = $this->createUser();
        $user->addGroup('admin');
        setting('Email.fromName', 'Ada');

        $this->actingAs($user)->post('/admin/settings/email', [
            'fromName'  => 'Mallory',
            'fromEmail' => 'mallory@example.com',
        ])->assertRedirectTo(ADMIN_AREA);

        $this->assertSame('Ada', setting('Email.fromName'));
    }

    public function testPurgingClearsTheLeftoverRowsWarning(): void
    {
        $this->useAppMailers(['main' => ['transport' => 'recording']], 'main');
        setting('Email.SMTPPass', 'hunter2');

        $this->assertCount(1, $this->xpath($this->page())->query('//*[@data-warning="leftoverRows"]'));

        service('commands')->run('bonfire:email-env', ['purge' => null, 'force' => null]);

        $this->assertCount(0, $this->xpath($this->page())->query('//*[@data-warning="leftoverRows"]'));
    }

    /**
     * Makes these the app's own Mailer config, so the bridge steps aside.
     *
     * @param array<string, mixed> $mailers
     */
    private function useAppMailers(array $mailers, string $default): void
    {
        $config                          = new class () extends PostalMailer {};
        $config->mailers                 = $mailers;
        $config->default                 = $default;
        $config->transports['recording'] = RecordingTransport::class;
        $config->transports['failing']   = FailingTransport::class;

        Factories::injectMock('config', 'Mailer', $config);
        Services::resetSingle('mailer');
    }

    private function page(): string
    {
        return (string) $this->actingAs($this->user)->get('/admin/settings/email')->getBody();
    }

    /**
     * The next page, with the session the last request left behind.
     */
    private function pageAfterRedirect(): string
    {
        $this->withSession();

        return $this->page();
    }

    private function sendTestMail(string $mailer, string $recipient): TestResponse
    {
        return $this->actingAs($this->user)->post(route_to('email-test-send'), [
            csrf_token() => csrf_hash(),
            'mailer'     => $mailer,
            'recipient'  => $recipient,
        ]);
    }

    private function xpath(string $html): DOMXPath
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);

        return new DOMXPath($dom);
    }

    /**
     * @param array<string, mixed> $fields
     *
     * @return list<string>
     */
    private function sortedKeysWithoutCsrf(array $fields): array
    {
        $keys = array_values(array_diff(array_keys($fields), [csrf_token()]));

        sort($keys);

        return $keys;
    }
}
