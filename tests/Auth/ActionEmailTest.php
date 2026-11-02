<?php

namespace Tests\Auth;

use Bonfire\Auth\Actions\Email2FA;
use Bonfire\Auth\Actions\EmailActivator;
use Bonfire\Auth\Mails\ActivationMail;
use Bonfire\Auth\Mails\TwoFactorMail;
use Bonfire\Users\Models\UserModel;
use Bonfire\Users\User;
use CodeIgniter\Shield\Authentication\Authenticators\Session;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;
use Myth\Postal\Email;
use Myth\Postal\Mailer;
use Myth\Postal\Transport\FakeTransport;
use Tests\Support\TestCase;

/**
 * Bonfire's own email actions send Postal Mailables, which give Shield's
 * email views the $user they print.
 *
 * @internal
 */
final class ActionEmailTest extends TestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace;
    private FakeTransport $mail;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mail = Mailer::fake();
    }

    protected function tearDown(): void
    {
        // The settings service caches values across tests.
        setting()->forget('Auth.actions');
        setting()->forget('Auth.allowRegistration');
        setting()->forget('Email.fromEmail');
        setting()->forget('Email.fromName');
        Services::resetSingle('mailer');

        parent::tearDown();
    }

    public function testEmailActivatorSendsTheActivationMail()
    {
        $this->startAction('register', EmailActivator::class);

        $response = $this->withSession()->get('auth/a/show');

        $response->assertOK();
        $this->assertTheOneMailSentIs(ActivationMail::class);
    }

    public function testEmail2FASendsTheTwoFactorMail()
    {
        $this->startAction('login', Email2FA::class);

        $response = $this->withSession()->post('auth/a/handle', ['email' => 'fred@example.com']);

        $response->assertOK();
        $this->assertTheOneMailSentIs(TwoFactorMail::class);
    }

    public function testRegisteringWithActivationSendsTheActivationMail()
    {
        setting('Auth.allowRegistration', true);
        $this->useActionAndFrom('register', EmailActivator::class);

        $this->post(route_to('register'), [
            'email'            => 'fred@example.com',
            'username'         => 'freddy101',
            'password'         => 'secret123abc!@',
            'password_confirm' => 'secret123abc!@',
        ])->assertRedirect();

        $this->withSession()->get('auth/a/show')->assertOK();

        $this->assertTheOneMailSentIs(ActivationMail::class);
    }

    public function testLoggingInWithEmail2FASendsTheTwoFactorMail()
    {
        $this->useActionAndFrom('login', Email2FA::class);

        /** @var User $user */
        $user = fake(UserModel::class, ['username' => 'freddy101']);
        $user->createEmailIdentity(['email' => 'fred@example.com', 'password' => 'secret123abc!@']);

        $this->post(route_to('login'), ['email' => 'fred@example.com', 'password' => 'secret123abc!@'])->assertRedirect();

        $this->withSession()->get('auth/a/show')->assertOK();
        $this->withSession()->post('auth/a/handle', ['email' => 'fred@example.com'])->assertOK();

        $this->assertTheOneMailSentIs(TwoFactorMail::class);
    }

    /**
     * @param class-string $mailable
     */
    private function assertTheOneMailSentIs(string $mailable): void
    {
        $this->mail->assertSentCount(1);
        $this->mail->assertSent($mailable, static fn (Email $email): bool => $email->to[0]->email === 'fred@example.com');

        // The Mailables don't set a From: the Settings one is filled in on send.
        $email = $this->mail->sent()[0];
        $this->assertSame('site@example.com', $email->from?->email);
        $this->assertSame('Fred Site', $email->from->name);
        $this->assertStringContainsString('freddy101', (string) $email->htmlBody);
    }

    /**
     * Turns on the given action and leaves a new user pending on it.
     */
    private function startAction(string $type, string $actionClass): void
    {
        $this->useActionAndFrom($type, $actionClass);

        /** @var User $user */
        $user = fake(UserModel::class, ['username' => 'freddy101']);
        $user->createEmailIdentity(['email' => 'fred@example.com', 'password' => 'secret123abc!@']);

        /** @var Session $authenticator */
        $authenticator = auth('session')->getAuthenticator();
        $authenticator->startLogin($user);
        $this->assertTrue($authenticator->startUpAction($type, $user));
    }

    private function useActionAndFrom(string $type, string $actionClass): void
    {
        setting('Email.fromEmail', 'site@example.com');
        setting('Email.fromName', 'Fred Site');
        setting('Auth.actions', [
            'login'    => null,
            'register' => null,
            $type      => $actionClass,
        ]);
    }
}
