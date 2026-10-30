<?php

namespace Tests\Users;

use Bonfire\Consent\Models\ConsentRecordModel;
use Bonfire\Users\User;
use CodeIgniter\Shield\Models\LoginModel;
use CodeIgniter\Shield\Models\TokenLoginModel;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class UserExportTest extends TestCase
{
    protected $refresh = true;
    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createUser();
        $this->admin->addGroup('superadmin');

        $this->user = $this->createUser(['email' => 'jo@example.com', 'username' => 'jo', 'first_name' => 'Jo', 'avatar' => 'jo.png']);
        $this->user->addGroup('user');
    }

    public function testDownloadsEverythingHeldAboutTheUserAsJson(): void
    {
        $this->user->saveMeta('company', 'Acme');
        model(LoginModel::class)->recordLoginAttempt('email_password', 'jo@example.com', true, '10.0.0.1', 'Firefox', $this->user->id);
        model(LoginModel::class)->recordLoginAttempt('email_password', 'jo@example.com', false, '10.0.0.2', 'Firefox');
        model(LoginModel::class)->recordLoginAttempt('email_password', 'someone@example.com', false, '10.0.0.3', 'Firefox');
        model(TokenLoginModel::class)->recordLoginAttempt('access_token', 'token', true, '10.0.0.4', 'curl', $this->user->id);
        $this->user->generateAccessToken('cli');
        model(ConsentRecordModel::class)->insert(['consent_id' => str_repeat('a', 32), 'user_id' => $this->user->id, 'consents' => '{"performance":1}']);
        model(ConsentRecordModel::class)->insert(['consent_id' => str_repeat('b', 32), 'user_id' => null, 'consents' => '{"performance":0}']);

        $response = $this->actingAs($this->admin)->get("admin/users/{$this->user->id}/export");

        $response->assertOK();
        $response->assertHeader('Content-Disposition', 'attachment; filename="user-' . $this->user->id . '-data.json"');
        $data = json_decode($response->response()->getBody(), true);

        $this->assertSame('jo', $data['users']['account']['username']);
        $this->assertSame('Jo', $data['users']['account']['first_name']);
        $this->assertSame('jo@example.com', $data['users']['email']);
        $this->assertSame(['company' => 'Acme'], $data['users']['meta']);
        $this->assertSame('jo.png', $data['users']['avatar']);
        $this->assertSame(['access_token', 'email_password'], $this->sorted(array_column($data['users']['identities'], 'type')));
        $this->assertSame(['10.0.0.1', '10.0.0.2'], $this->sorted(array_column($data['users']['logins'], 'ip_address')));
        $this->assertSame(['10.0.0.4'], array_column($data['users']['tokenLogins'], 'ip_address'));
        $this->assertSame([['performance' => 1]], array_column($data['consent'], 'consents'));
    }

    public function testExportHoldsNoSecrets(): void
    {
        $token = $this->user->generateAccessToken('cli');
        $hash  = model('CodeIgniter\Shield\Models\UserIdentityModel')->getIdentityByType($this->user, 'email_password')->secret2;

        $body = $this->actingAs($this->admin)->get("admin/users/{$this->user->id}/export")->response()->getBody();
        $this->assertStringContainsString('"identities"', $body);

        $this->assertStringNotContainsString(json_encode($hash), $body);
        $this->assertStringNotContainsString($token->secret, $body);
        $this->assertStringNotContainsString(hash('sha256', $token->raw_token), $body);
        $this->assertStringNotContainsString('secret', $body);
    }

    public function testEachExportIsLogged(): void
    {
        $this->actingAs($this->admin)->get("admin/users/{$this->user->id}/export");

        $this->assertLogged('notice', "User {$this->admin->id} exported the personal data of user {$this->user->id}.");
    }

    public function testAdminsMayExport(): void
    {
        $admin = $this->createUser();
        $admin->addGroup('admin');

        $this->actingAs($admin)->get("admin/users/{$this->user->id}/export")->assertOK();
        $this->assertStringContainsString(
            'href="' . site_url("admin/users/{$this->user->id}/export") . '"',
            $this->actingAs($admin)->get("admin/users/{$this->user->id}")->getBody(),
        );
    }

    public function testUsersWithoutTheExportPermissionAreRefused(): void
    {
        $developer = $this->createUser();
        $developer->addGroup('developer');

        $this->actingAs($developer)->get("admin/users/{$this->user->id}/export")->assertRedirect();
        $this->assertStringNotContainsString(
            site_url("admin/users/{$this->user->id}/export"),
            $this->actingAs($developer)->get("admin/users/{$this->user->id}")->getBody(),
        );
    }

    /**
     * @param list<string> $values
     *
     * @return list<string>
     */
    private function sorted(array $values): array
    {
        sort($values);

        return $values;
    }
}
