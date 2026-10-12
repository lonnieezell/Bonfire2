<?php

namespace Tests\Auth;

use Bonfire\Auth\Actions\Email2FA;
use Bonfire\Auth\Actions\EmailActivator;
use Bonfire\Users\Models\UserModel;
use Bonfire\Users\User;
use CodeIgniter\Shield\Authentication\Authenticators\Session;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\TestCase;

/**
 * Bonfire's own email actions must give Shield's email views the $user
 * they print.
 *
 * @internal
 */
final class ActionEmailTest extends TestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace;

    protected function tearDown(): void
    {
        // The settings service caches values across tests.
        setting()->forget('Auth.actions');

        parent::tearDown();
    }

    public function testEmailActivatorSendsEmailWithUsername()
    {
        $this->startAction('register', EmailActivator::class);

        $response = $this->withSession()->get('auth/a/show');

        $response->assertOK();
        $this->assertStringContainsString('freddy101', (string) service('email')->archive['body']);
    }

    public function testEmail2FASendsEmailWithUsername()
    {
        $this->startAction('login', Email2FA::class);

        $response = $this->withSession()->post('auth/a/handle', ['email' => 'fred@example.com']);

        $response->assertOK();
        $this->assertStringContainsString('freddy101', (string) service('email')->archive['body']);
    }

    /**
     * Turns on the given action and leaves a new user pending on it.
     */
    private function startAction(string $type, string $actionClass): void
    {
        setting('Auth.actions', [
            'login'    => null,
            'register' => null,
            $type      => $actionClass,
        ]);

        /** @var User $user */
        $user = fake(UserModel::class, ['username' => 'freddy101']);
        $user->createEmailIdentity(['email' => 'fred@example.com', 'password' => 'secret123abc!@']);

        /** @var Session $authenticator */
        $authenticator = auth('session')->getAuthenticator();
        $authenticator->startLogin($user);
        $this->assertTrue($authenticator->startUpAction($type, $user));
    }
}
