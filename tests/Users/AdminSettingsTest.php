<?php

namespace Tests\Users;

use Bonfire\Auth\Actions\Email2FA;
use Bonfire\Auth\Actions\EmailActivator;
use Bonfire\Users\User;
use CodeIgniter\Shield\Authentication\Passwords\DictionaryValidator;
use CodeIgniter\Shield\Authentication\Passwords\PwnedValidator;
use Tests\Support\SubmitsForms;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class AdminSettingsTest extends TestCase
{
    use SubmitsForms;

    protected $refresh = true;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser();
        $this->user->addGroup('superadmin');
    }

    public function testCanSeePage()
    {
        $result = $this->actingAs($this->user)
            ->get('/admin/settings/users');

        $result->assertStatus(200);
        $result->assertSee('Registration');
    }

    public function testSubmitForm()
    {
        $result = $this->actingAs($this->user)
            ->post('/admin/settings/users', [
                'allowRegistration'     => 1,
                'emailActivation'       => 1,
                'allowRemember'         => 'on',
                'rememberLength'        => 55,
                'email2FA'              => 1,
                'minimumPasswordLength' => 10,
                'avatarResize'          => 1,
                'avatarSize'            => 180,
                'validators'            => [
                    'CodeIgniter\Shield\Authentication\Passwords\CompositionValidator',
                ],
                'defaultGroup' => 'developer',
            ]);

        $result->assertRedirect();

        $this->assertTrue(setting('Auth.allowRegistration'));
        $this->assertSame(10, setting('Auth.minimumPasswordLength'));
        $this->assertSame('developer', setting('AuthGroups.defaultGroup'));
        $this->assertSame(['CodeIgniter\Shield\Authentication\Passwords\CompositionValidator'], setting('Auth.passwordValidators'));
        // Compared per key: the order depends on what earlier tests left in the settings cache.
        $this->assertSame('1', setting('Auth.actions')['login']);
        $this->assertSame('1', setting('Auth.actions')['register']);
    }

    public function testEverySettingRoundTripsThroughTheForm(): void
    {
        $settings = [
            'Auth.allowRegistration'     => true,
            'Auth.minimumPasswordLength' => 12,
            'Auth.passwordValidators'    => [DictionaryValidator::class, PwnedValidator::class],
            'AuthGroups.defaultGroup'    => 'beta',
            'Auth.actions'               => ['register' => EmailActivator::class, 'login' => Email2FA::class],
            'Users.useGravatar'          => '1',
            'Users.gravatarDefault'      => 'retro',
            'Users.avatarNameBasis'      => 'username',
            'Users.avatarResize'         => true,
            'Users.avatarSize'           => 200,
        ];

        foreach ($settings as $key => $value) {
            setting($key, $value);
        }
        setting('Auth.sessionConfig', ['allowRemembering' => '1', 'rememberLength' => (string) WEEK] + setting('Auth.sessionConfig'));

        $this->submitTheForm();

        foreach ($settings as $key => $value) {
            $this->assertSame($value, setting($key), $key);
        }
        $this->assertTrue((bool) setting('Auth.sessionConfig')['allowRemembering']);
        $this->assertSame((string) WEEK, setting('Auth.sessionConfig')['rememberLength']);
    }

    public function testSwitchedOffActionsAreSavedAsNone(): void
    {
        setting('Auth.actions', ['register' => null, 'login' => null]);
        setting('Users.useGravatar', false);
        setting('Auth.allowRegistration', false);

        $this->submitTheForm();

        $this->assertSame(['register' => null, 'login' => null], setting('Auth.actions'));
        $this->assertFalse((bool) setting('Users.useGravatar'));
        $this->assertFalse(setting('Auth.allowRegistration'));
    }

    public function testPasswordValidatorsUseSwitchesAndWarnAboutOverlapAndNone(): void
    {
        $html = $this->actingAs($this->user)->get('/admin/settings/users')->getBody();

        $this->assertMatchesRegularExpression('#role="switch" class="bf-switch-input" name="validators\[\]" value="CodeIgniter\\\\Shield\\\\Authentication\\\\Passwords\\\\PwnedValidator"#', $html);
        $this->assertStringContainsString('x-show="validators.length === 0"', $html);
        $this->assertStringContainsString('x-show="overlap"', $html);
    }

    public function testPageNeedsUsersSettingsPermission(): void
    {
        $user = $this->createUser();
        $user->addPermission('admin.access');

        $this->actingAs($user)->get('/admin/settings/users')->assertRedirectTo(ADMIN_AREA);
    }

    public function testSaveNeedsUsersSettingsPermission(): void
    {
        $user = $this->createUser();
        $user->addPermission('admin.access');
        setting('Auth.minimumPasswordLength', 8);

        $this->actingAs($user)->post('/admin/settings/users', [
            'minimumPasswordLength' => 20,
            'defaultGroup'          => 'user',
            'avatarResize'          => 0,
        ])->assertRedirectTo(ADMIN_AREA);

        $this->assertSame(8, setting('Auth.minimumPasswordLength'));
    }

    private function submitTheForm(): void
    {
        $html   = $this->actingAs($this->user)->get('/admin/settings/users')->getBody();
        $fields = $this->formFields($html, site_url(ADMIN_AREA . '/settings/users'));

        $this->actingAs($this->user)->post('/admin/settings/users', $fields)->assertSessionMissing('errors');
    }
}
