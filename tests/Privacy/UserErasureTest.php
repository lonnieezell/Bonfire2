<?php

namespace Tests\Privacy;

use Bonfire\Consent\ConsentPersonalData;
use Bonfire\Consent\Models\ConsentRecordModel;
use Bonfire\Privacy\PersonalData;
use Bonfire\Users\Models\UserModel;
use Bonfire\Users\User;
use Bonfire\Users\UserPersonalData;
use CodeIgniter\Config\Services;
use CodeIgniter\Shield\Models\LoginModel;
use CodeIgniter\Shield\Models\RememberModel;
use CodeIgniter\Shield\Models\TokenLoginModel;
use Tests\Support\TestCase;

/**
 * Erasing a user's data with Bonfire's own providers.
 *
 * @internal
 */
final class UserErasureTest extends TestCase
{
    protected $refresh = true;
    private PersonalData $data;
    private User $user;
    private User $other;
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $relativeDir = 'uploads/avatars-test-' . bin2hex(random_bytes(4));
        $this->dir   = FCPATH . $relativeDir;
        setting('Users.avatarDirectory', $relativeDir);
        mkdir($this->dir, 0755, true);
        file_put_contents($this->dir . '/jo.png', 'img');

        Services::resetSingle('personalData');
        $this->data = service('personalData')
            ->register('users', new UserPersonalData())
            ->register('consent', new ConsentPersonalData());

        $this->user  = $this->createUser(['email' => 'jo@example.com', 'password' => 'secret123', 'username' => 'jo', 'first_name' => 'Jo', 'last_name' => 'Doe', 'avatar' => 'jo.png']);
        $this->other = $this->createUser(['email' => 'al@example.com', 'username' => 'al']);

        foreach ([$this->user, $this->other] as $user) {
            $user->saveMeta('company', 'Acme');
            $user->generateAccessToken('cli');
            model(LoginModel::class)->recordLoginAttempt('email_password', $user->email, true, '10.0.0.1', 'Firefox', $user->id);
            model(LoginModel::class)->recordLoginAttempt('email_password', $user->email, false, '10.0.0.2', 'Firefox');
            model(TokenLoginModel::class)->recordLoginAttempt('access_token', 'token', true, '10.0.0.3', 'curl', $user->id);
            model(RememberModel::class)->rememberUser($user, 'selector' . $user->id, 'hash', '2099-01-01 00:00:00');
            model(ConsentRecordModel::class)->insert(['consent_id' => str_repeat('a', 32), 'user_id' => $user->id, 'consents' => '{}']);
        }
    }

    protected function tearDown(): void
    {
        helper('filesystem');
        delete_files($this->dir, true);
        rmdir($this->dir);
        Services::resetSingle('personalData');

        parent::tearDown();
    }

    public function testLeavesAnAnonymousRowThatStillExists(): void
    {
        $this->data->erase($this->user);

        $row = $this->db->table('users')->where('id', $this->user->id)->get()->getRowArray();
        $this->assertSame('deleted-user-' . $this->user->id, $row['username']);
        $this->assertNull($row['first_name']);
        $this->assertNull($row['last_name']);
        $this->assertNull($row['avatar']);
        $this->assertSame(0, (int) $row['active']);
        $this->assertNotNull($row['deleted_at']);
        $this->assertNotNull($row['erased_at']);
        $this->assertFileDoesNotExist($this->dir . '/jo.png');
    }

    public function testRemovesWhatIdentifiesTheUser(): void
    {
        $this->data->erase($this->user);

        $this->dontSeeInDatabase('auth_identities', ['user_id' => $this->user->id]);
        $this->dontSeeInDatabase('auth_remember_tokens', ['user_id' => $this->user->id]);
        $this->dontSeeInDatabase('meta_info', ['resource_id' => $this->user->id]);
        $this->dontSeeInDatabase('consent_records', ['user_id' => $this->user->id]);
        $this->assertNull(model(UserModel::class)->findByCredentials(['email' => 'jo@example.com']));
        $this->assertFalse(auth()->check(['email' => 'jo@example.com', 'password' => 'secret123'])->isOK());
    }

    public function testKeepsLoginRowsWithoutTheEmail(): void
    {
        $this->data->erase($this->user);

        $this->dontSeeInDatabase('auth_logins', ['identifier' => 'jo@example.com']);
        $this->assertSame(2, $this->db->table('auth_logins')->where('identifier', 'deleted-user-' . $this->user->id)->countAllResults());
        $this->seeInDatabase('auth_logins', ['user_id' => $this->user->id, 'ip_address' => '10.0.0.1']);
        $this->seeInDatabase('auth_token_logins', ['user_id' => $this->user->id, 'identifier' => 'deleted-user-' . $this->user->id]);
    }

    public function testLeavesOtherUsersAlone(): void
    {
        $this->data->erase($this->user);

        $this->seeInDatabase('users', ['id' => $this->other->id, 'username' => 'al', 'erased_at' => null]);
        $this->assertSame(2, $this->db->table('auth_identities')->where('user_id', $this->other->id)->countAllResults());
        $this->seeInDatabase('auth_remember_tokens', ['user_id' => $this->other->id]);
        $this->seeInDatabase('meta_info', ['resource_id' => $this->other->id]);
        $this->seeInDatabase('consent_records', ['user_id' => $this->other->id]);
        $this->assertSame(2, $this->db->table('auth_logins')->where('identifier', 'al@example.com')->countAllResults());
        $this->seeInDatabase('auth_token_logins', ['user_id' => $this->other->id, 'identifier' => 'token']);
    }
}
