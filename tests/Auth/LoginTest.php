<?php

namespace Tests\Auth;

use Bonfire\Users\Models\UserModel;
use Bonfire\Users\User;
use CodeIgniter\Shield\Authentication\Actions\Email2FA;
use CodeIgniter\Test\DatabaseTestTrait;
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
        // primitive perhaps, but works: make sure we are logged out first:
        $response = $this->get(route_to('logout'));

        $response = $this->get(route_to('login'));

        $response->assertOK();
        $response->assertSee(lang('Auth.login'));
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

        $response->assertRedirectTo(site_url());

        $response->assertSessionHas('logged_in');
    }

    public function testLoginSuccessAdmin()
    {
        /**
         * @var User
         */
        $user = fake(UserModel::class);
        $user->createEmailIdentity(['email' => 'foo@example.com', 'password' => 'alsdkfja;sldkfj']);
        $user->addGroup('superadmin');

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
        // primitive perhaps, but works: make sure we are logged out first:
        $response = $this->get(route_to('logout'));

        helper('setting');
        setting('Auth.sessionConfig', [
            'field'              => 'logged_in',
            'allowRemembering'   => true,
            'rememberCookieName' => 'remember',
            'rememberLength'     => 30 * DAY,
        ]);

        $response = $this->get(route_to('login'));
        $response->assertOK();
        $response->assertNotRedirect();
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
