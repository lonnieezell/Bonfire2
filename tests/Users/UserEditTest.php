<?php

namespace Tests\Users;

use Bonfire\Users\Models\UserModel;
use Bonfire\Users\User;
use CodeIgniter\Shield\Models\LoginModel;
use Tests\Support\TestCase;

/**
 * The user edit screens: header, tabs, details, permissions and security.
 *
 * @internal
 */
final class UserEditTest extends TestCase
{
    protected $refresh = true;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        setting('Auth.actions', ['login' => null, 'register' => null]);

        $this->admin = $this->createUser(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'username' => 'ada', 'active' => 1]);
        $this->admin->addGroup('superadmin');

        // Other tests leave a changed matrix in the settings cache.
        setting('AuthGroups.matrix', config('AuthGroups')->matrix);
    }

    public function testHeadShowsAvatarNameStatusAndEmail(): void
    {
        $html = $this->page($this->admin);

        $this->assertMatchesRegularExpression('#<a class="bf-back" href="' . preg_quote(site_url(ADMIN_AREA . '/users'), '#') . '">#', $html);
        $this->assertMatchesRegularExpression('#<div class="bf-user-head">.*?<span style="width: 48px; height: 48px;#s', $html);
        $this->assertMatchesRegularExpression('#<h1>Ada Lovelace</h1>\s*<span class="bf-status bf-status-ok"><span class="bf-dot" aria-hidden="true"></span>Active</span>#', $html);
        $this->assertStringContainsString(esc($this->admin->email) . ' &middot; Last active Just now', $html);
    }

    public function testDetailsHasProfileAccessGroupsAndMetaCardsAndASaveBar(): void
    {
        $user = $this->betaUserWithLogsView();

        $html = $this->page($user);

        foreach (['Profile', 'Access', 'Groups', 'Example Fields'] as $card) {
            $this->assertStringContainsString("<h2>{$card}</h2>", $html);
        }
        $this->assertMatchesRegularExpression('#<input type="checkbox" role="switch" class="bf-switch-input" name="activate" value="1"[^>]* checked>#', $html);
        $this->assertMatchesRegularExpression('#<input type="checkbox" role="switch" class="bf-switch-input" name="ban" value="1"[^>]* x-model="isBanned"#', $html);
        $this->assertMatchesRegularExpression(
            '#<label class="bf-group-card">\s*<input type="checkbox" name="groups\[\]" value="beta"[^>]*checked\s*>\s*<span class="bf-group-card-title">Beta User</span>\s*<span class="bf-group-card-desc">Has access to beta-level features.</span>\s*<span class="bf-group-card-meta">1 permission</span>#',
            $html,
        );
        $this->assertStringContainsString('<span class="bf-group-card-meta">22 permissions</span>', $html);
        $this->assertStringContainsString('class="bf-floating-bar" x-data="bfSaveBar"', $html);
    }

    public function testDetailsEscapeStoredValues(): void
    {
        $user = $this->createUser(['first_name' => '"><script>x</script>', 'username' => 'grace', 'active' => 1]);
        $user->saveMeta('blog', '"><b>meta</b>');

        $html = (string) $this->actingAs($this->admin)->get('/admin/users/' . $user->id)->response()->getBody();

        $this->assertStringNotContainsString('"><script>x</script>', $html);
        $this->assertStringContainsString('value="&quot;&gt;&lt;script&gt;x&lt;/script&gt;"', $html);
        $this->assertStringNotContainsString('"><b>meta</b>', $html);
    }

    public function testNewUserFormHasNoTabsOrAccessCard(): void
    {
        $result = $this->actingAs($this->admin)->get('/admin/users/new');
        $result->assertOK();
        $html = $result->getBody();

        $this->assertMatchesRegularExpression('#<h1>New User</h1>#', $html);
        $this->assertStringNotContainsString('bf-tabs', $html);
        $this->assertStringNotContainsString('<h2>Access</h2>', $html);
        $this->assertStringContainsString('<h2>Profile</h2>', $html);
    }

    public function testSelfEditorWithoutUsersEditCannotTickGroups(): void
    {
        $me = $this->createUser(['username' => 'self', 'active' => 1]);
        $me->addGroup('user');
        $me->addPermission('admin.access', 'me.edit');

        $result = $this->actingAs($me)->get('/admin/users/' . $me->id);
        $result->assertOK();

        $this->assertStringContainsString('name="groups[]"', $result->getBody());
        $this->assertDoesNotMatchRegularExpression('#name="groups\[\]" value="[^"]*"(?:(?!disabled)[^>])*>#', $result->getBody());
    }

    public function testPermissionsTabCountsEffectivePermissions(): void
    {
        $html = $this->page($this->betaUserWithLogsView());

        $this->assertMatchesRegularExpression('#Permissions\s*<span class="bf-count">2</span>#', $html);
    }

    public function testPermissionsAreGroupedByPrefixAndInheritedOnesAreLocked(): void
    {
        $html = $this->page($this->betaUserWithLogsView(), 'permissions');

        $this->assertMatchesRegularExpression('#<h2>Beta</h2>.*?<label class="bf-setting-label" for="perm-beta-access">beta.access</label>#s', $html);
        $this->assertMatchesRegularExpression('#<span class="bf-locked"><i class="fas fa-lock" aria-hidden="true"></i> via Beta User</span>#', $html);
        $this->assertStringNotContainsString('value="beta.access"', $html);
        $this->assertMatchesRegularExpression('#name="permissions\[\]" value="logs.view"[^>]* checked>#', $html);
        $this->assertMatchesRegularExpression('#name="permissions\[\]" value="logs.manage"[^>]*(?<! checked)>#', $html);
    }

    public function testOnlyAdminManagersCanGrantUserManagementPermissions(): void
    {
        $manager = $this->createUser(['username' => 'linus', 'active' => 1]);
        $manager->addGroup('admin');
        $manager->addPermission('users.view');

        $result = $this->actingAs($manager)->get('/admin/users/' . $this->betaUserWithLogsView()->id . '/permissions');

        $this->assertMatchesRegularExpression('#value="users.edit"[^>]* disabled#', $result->getBody());
        $this->assertDoesNotMatchRegularExpression('#value="logs.manage"[^>]* disabled#', $result->getBody());
    }

    public function testSavingPermissionsReplacesDirectGrants(): void
    {
        $user = $this->betaUserWithLogsView();

        $this->actingAs($this->admin)->post('/admin/users/' . $user->id . '/permissions', ['permissions' => ['logs.manage']]);

        $user = model(UserModel::class)->find($user->id);
        $this->assertTrue($user->hasPermission('logs.manage'));
        $this->assertFalse($user->hasPermission('logs.view'));
    }

    public function testSavingPermissionsIgnoresUnknownValues(): void
    {
        $user = $this->betaUserWithLogsView();

        // An Admin theme copy published before `offValue` posts "0" for each switch that is off.
        $result = $this->actingAs($this->admin)->post('/admin/users/' . $user->id . '/permissions', ['permissions' => ['0', 'logs.manage', '0']]);

        $result->assertRedirect();
        $user = model(UserModel::class)->find($user->id);
        $this->assertTrue($user->hasPermission('logs.manage'));
        $this->assertFalse($user->hasPermission('logs.view'));
    }

    public function testSecurityHasAPasswordCardWithMeterAndLoginPills(): void
    {
        $logins = model(LoginModel::class);
        $logins->recordLoginAttempt('email_password', $this->admin->email, true, '10.0.0.1', 'Firefox', $this->admin->id);
        $logins->recordLoginAttempt('email_password', $this->admin->email, false, '10.0.0.2', 'Curl');

        $html = $this->page($this->admin, 'security');

        $this->assertStringContainsString('<h2>Change Password</h2>', $html);
        $this->assertStringContainsString('<div id="pass-meter"', $html);
        $this->assertStringContainsString('auth/js/passStrength', $html);
        $this->assertStringContainsString('<h2>Recent Logins</h2>', $html);
        $this->assertMatchesRegularExpression('#10.0.0.1</td>.*?bf-status-ok"><span class="bf-dot" aria-hidden="true"></span>Success#s', $html);
        $this->assertMatchesRegularExpression('#10.0.0.2</td>.*?bf-status-danger"><span class="bf-dot" aria-hidden="true"></span>Failed#s', $html);
    }

    private function betaUserWithLogsView(): User
    {
        $user = $this->createUser(['username' => 'grace', 'active' => 1]);
        $user->addGroup('beta');
        $user->addPermission('logs.view');

        return $user;
    }

    private function page(User $user, string $tab = ''): string
    {
        $result = $this->actingAs($this->admin)->get('/admin/users/' . $user->id . ($tab === '' ? '' : '/' . $tab));
        $result->assertOK();

        return $result->getBody();
    }
}
