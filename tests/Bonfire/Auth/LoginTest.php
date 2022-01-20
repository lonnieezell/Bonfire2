<?php

namespace Tests\Bonfire\Auth;

use App\Entities\User;
use App\Models\UserModel;
use CodeIgniter\Test\DatabaseTestTrait;
use Sparks\Shield\Authentication\Actions\Email2FA;
use Sparks\Shield\Authentication\Passwords;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class LoginTest extends TestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace;

    public function testCanViewLoginPage()
    {
        $response = $this->get(route_to('login'));

        $response->assertOK();
        $response->assertSee('Login');
    }

    public function testLoginSuccess()
    {
        /**
         * @var User
         */
        $user = fake(UserModel::class);
        $user->createEmailIdentity(['email' => 'foo@example.com', 'password' => 'alsdkfja;sldkfj']);

        // Submit registration form
        $response = $this->post(route_to('login'), [
            'email'    => 'foo@example.com',
            'password' => 'alsdkfja;sldkfj',
        ]);

        $response->assertRedirectTo(site_url('admin'));

        $response->assertSessionHas('logged_in');
    }

    public function testAllowRememberShowsUp()
    {
        setting('Auth.sessionConfig', [
            'field'              => 'logged_in',
            'allowRemembering'   => true,
            'rememberCookieName' => 'remember',
            'rememberLength'     => 30 * DAY,
        ]);

        $response = $this->get(route_to('login'));

        $response->assertSee(lang('Auth.rememberMe'));

        setting('Auth.sessionConfig', [
            'field'              => 'logged_in',
            'allowRemembering'   => false,
            'rememberCookieName' => 'remember',
            'rememberLength'     => 30 * DAY,
        ]);

        $response = $this->get(route_to('login'));

        $response->assertDontSee(lang('Auth.rememberMe'));
    }

    public function testLoginTo2FAAction()
    {
        setting('Auth.actions', [
            'login'    => Email2FA::class,
            'register' => null,
        ]);

        /**
         * @var User
         */
        $user = fake(UserModel::class);
        $user->createEmailIdentity(['email' => 'foo@example.com', 'password' => 'alsdkfja;sldkfj']);

        // Submit registration form
        $response = $this->post(route_to('login'), [
            'email'    => 'foo@example.com',
            'password' => 'alsdkfja;sldkfj',
        ]);

        $response->assertRedirectTo('/auth/a/show');
    }
}
