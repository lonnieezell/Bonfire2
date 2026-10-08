<?php

namespace Tests\Users;

use Bonfire\Users\Libraries\AvatarStorage;
use Bonfire\Users\Libraries\SaveUser;
use Bonfire\Users\Libraries\UserAccess;
use Bonfire\Users\Models\UserModel;
use Bonfire\Users\User;
use CodeIgniter\Shield\Authentication\Actions\EmailActivator;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class SaveUserTest extends TestCase
{
    protected $refresh = true;

    private function saver(User $actor): SaveUser
    {
        return new SaveUser(new UserAccess($actor), new AvatarStorage(), new UserModel());
    }

    private function superadmin(): User
    {
        $user = $this->createUser();
        $user->addGroup('superadmin');

        return $user;
    }

    private function editor(): User
    {
        $user = $this->createUser();
        $user->addPermission('users.edit');

        return $user;
    }

    public function testUpdatesAnExistingUser(): void
    {
        $target = $this->createUser();

        $this->saver($this->superadmin())->handle($target, [
            'email'      => $target->email,
            'username'   => 'freddy',
            'first_name' => 'Fred',
            'last_name'  => 'Flintstone',
            'groups'     => ['beta'],
        ]);

        $this->seeInDatabase('users', ['id' => $target->id, 'first_name' => 'Fred', 'last_name' => 'Flintstone']);
        $this->assertTrue(model(UserModel::class)->find($target->id)->inGroup('beta'));
    }

    public function testCreatesANewActiveUserWithAnEmailIdentity(): void
    {
        $user = $this->saver($this->superadmin())->handle(new User(), [
            'email'    => 'new@example.com',
            'username' => 'newbie',
            'password' => 'a-long-secret-1',
        ]);

        $this->assertNotNull($user->id);
        $this->seeInDatabase('users', ['id' => $user->id, 'username' => 'newbie', 'active' => 1]);
        $this->seeInDatabase('auth_identities', ['user_id' => $user->id, 'type' => 'email_password', 'secret' => 'new@example.com']);
    }

    public function testChangesTheEmailOfAnExistingUser(): void
    {
        $target = $this->createUser();

        $this->saver($this->superadmin())->handle($target, ['email' => 'changed@example.com', 'username' => $target->username]);

        $this->seeInDatabase('auth_identities', ['user_id' => $target->id, 'secret' => 'changed@example.com']);
    }

    public function testBansAndUnbansWhenAllowed(): void
    {
        $target = $this->createUser();
        $saver  = $this->saver($this->superadmin());

        $saver->handle($target, ['email' => $target->email, 'ban' => '1', 'ban_reason' => 'spam']);
        $this->assertTrue(model(UserModel::class)->find($target->id)->isBanned());

        $saver->handle(model(UserModel::class)->find($target->id), ['email' => $target->email, 'ban' => '0']);
        $this->assertFalse(model(UserModel::class)->find($target->id)->isBanned());
    }

    public function testLeavesBanAloneWhenTheFlagIsNeitherZeroNorOne(): void
    {
        $target = $this->createUser();
        $saver  = $this->saver($this->superadmin());
        $saver->handle($target, ['email' => $target->email, 'ban' => '1']);

        $saver->handle(model(UserModel::class)->find($target->id), ['email' => $target->email, 'ban' => '2']);

        $this->assertTrue(model(UserModel::class)->find($target->id)->isBanned());
    }

    public function testCannotBanAnAdminWithoutManageAdmins(): void
    {
        $admin = $this->createUser();
        $admin->addGroup('admin');

        $this->saver($this->editor())->handle($admin, ['email' => $admin->email, 'ban' => '1']);

        $this->assertFalse(model(UserModel::class)->find($admin->id)->isBanned());
    }

    public function testCannotBanYourself(): void
    {
        $actor = $this->editor();

        $this->saver($actor)->handle($actor, ['email' => $actor->email, 'ban' => '1']);

        $this->assertFalse(model(UserModel::class)->find($actor->id)->isBanned());
    }

    public function testGroupsEditorKeepsAdminGroupsUntouched(): void
    {
        $admin = $this->createUser();
        $admin->addGroup('admin');

        $this->saver($this->editor())->handle($admin, [
            'email'  => $admin->email,
            'groups' => ['beta', 'superadmin'],
        ]);

        $groups = model(UserModel::class)->find($admin->id)->getGroups();
        $this->assertEqualsCanonicalizing(['admin', 'beta'], $groups);
    }

    public function testGroupsAreLeftAloneForSelfEditors(): void
    {
        $actor = $this->createUser();
        $actor->addPermission('me.edit');
        $actor->addGroup('beta');

        $this->saver($actor)->handle($actor, ['email' => $actor->email, 'first_name' => 'Me', 'groups' => ['superadmin']]);

        $this->assertSame(['beta'], model(UserModel::class)->find($actor->id)->getGroups());
    }

    public function testActivatesAnInactiveUserOnRequest(): void
    {
        // Users only count as "not activated" when the site requires activation.
        setting('Auth.actions', ['register' => EmailActivator::class, 'login' => null]);
        $target = $this->createUser(['active' => 0]);

        $this->saver($this->superadmin())->handle($target, ['email' => $target->email, 'activate' => '1']);

        $this->seeInDatabase('users', ['id' => $target->id, 'active' => 1]);
    }
}
