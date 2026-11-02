<?php

declare(strict_types=1);

namespace Tests\Auth;

use CodeIgniter\Config\Factories;
use CodeIgniter\Shield\Authentication\Authenticators\Session;
use CodeIgniter\Shield\Entities\UserIdentity;
use CodeIgniter\Shield\Models\UserIdentityModel;
use Config\Services;
use Myth\Postal\Config\Mailer as PostalMailer;
use Tests\Support\Email\RecordingTransport;
use Tests\Support\TestCase;

/**
 * The magic link stays on Shield's controller and emailer(), and reaches the
 * mailer the app configured through Postal's legacy adapter.
 *
 * @internal
 */
final class MagicLinkEmailTest extends TestCase
{
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();

        RecordingTransport::reset();

        // CIUnitTestCase mocks the email service; these tests need Postal's.
        Services::resetSingle('email');
        Services::resetSingle('mailer');
        Services::resetSingle('settings');
    }

    protected function tearDown(): void
    {
        Services::resetSingle('email');
        Services::resetSingle('mailer');
        Services::resetSingle('settings');
        Factories::reset('config');

        parent::tearDown();
    }

    public function testShieldsEmailGoesThroughTheConfiguredMailer(): void
    {
        setting('Email.fromEmail', 'site@example.com');
        setting('Email.fromName', 'The Site');
        setting('Auth.allowMagicLinkLogins', true);

        $config                    = new class () extends PostalMailer {};
        $config->default           = 'app';
        $config->mailers['app']    = ['transport' => 'app'];
        $config->transports['app'] = RecordingTransport::class;
        Factories::injectMock('config', 'Mailer', $config);

        $user = $this->createUser(['email' => 'fred@example.com']);

        $this->post('login/magic-link', ['email' => 'fred@example.com'])->assertOK();

        $this->assertCount(1, RecordingTransport::$sent);

        $sent = RecordingTransport::$sent[0];
        $this->assertSame(['transport' => 'app'], $sent['settings']);
        $this->assertSame('fred@example.com', $sent['email']->to[0]->email);
        $this->assertSame('site@example.com', $sent['email']->from?->email);
        // It's Shield's message, not a Bonfire Mailable, and carries Shield's token.
        $this->assertNull($sent['email']->mailableClass);

        $identity = model(UserIdentityModel::class)->getIdentityByType($user, Session::ID_TYPE_MAGIC_LINK);
        $this->assertInstanceOf(UserIdentity::class, $identity);
        $this->assertStringContainsString($identity->secret, (string) $sent['email']->htmlBody);
    }
}
