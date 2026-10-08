<?php

namespace Tests\Users;

use Bonfire\Users\Libraries\UserAccess;
use Bonfire\Users\User;
use Tests\Support\TestCase;

/**
 * Pure rule tests: the stub users answer permission and group
 * questions from memory, so neither HTTP nor the database is involved.
 *
 * @internal
 */
final class UserAccessTest extends TestCase
{
    protected $migrate = false;

    /**
     * @param list<string> $groups
     * @param list<string> $permissions
     */
    private function user(int $id, array $groups = [], array $permissions = []): User
    {
        return new class ($id, $groups, $permissions) extends User {
            /**
             * @param list<string> $stubGroups
             * @param list<string> $stubPermissions
             */
            public function __construct(int $id, private readonly array $stubGroups, private readonly array $stubPermissions)
            {
                parent::__construct(['id' => $id]);
            }

            public function can(string ...$permissions): bool
            {
                return array_intersect($permissions, $this->stubPermissions) !== [];
            }

            public function hasPermission(string $permission): bool
            {
                return in_array($permission, $this->stubPermissions, true);
            }

            public function inGroup(string ...$groups): bool
            {
                return array_intersect($groups, $this->stubGroups) !== [];
            }

            public function getGroups(): array
            {
                return $this->stubGroups;
            }
        };
    }

    public function testEditFormOpensForUserManagersAndForSelfWithMePermissions(): void
    {
        $this->assertTrue((new UserAccess($this->user(1, [], ['users.edit'])))->canOpenEditForm(2));
        $this->assertTrue((new UserAccess($this->user(1, [], ['me.edit'])))->canOpenEditForm(1));
        $this->assertTrue((new UserAccess($this->user(1, [], ['me.security'])))->canOpenEditForm(1));
        $this->assertFalse((new UserAccess($this->user(1, [], ['me.edit'])))->canOpenEditForm(2));
        $this->assertFalse((new UserAccess($this->user(1)))->canOpenEditForm(1));
    }

    public function testSavingNeedsEditPermissionOrMeEditOnSelf(): void
    {
        $this->assertTrue((new UserAccess($this->user(1, [], ['users.edit'])))->canSave(2));
        $this->assertTrue((new UserAccess($this->user(1, [], ['users.edit'])))->canSave(null));
        $this->assertTrue((new UserAccess($this->user(1, [], ['me.edit'])))->canSave(1));
        $this->assertFalse((new UserAccess($this->user(1, [], ['me.security'])))->canSave(1));
        $this->assertFalse((new UserAccess($this->user(1, [], ['me.edit'])))->canSave(null));
    }

    public function testSecurityActionsNeedEditPermissionOrMeSecurityOnSelf(): void
    {
        $this->assertTrue((new UserAccess($this->user(1, [], ['users.edit'])))->canManageSecurity(2));
        $this->assertTrue((new UserAccess($this->user(1, [], ['me.security'])))->canManageSecurity(1));
        $this->assertFalse((new UserAccess($this->user(1, [], ['me.edit'])))->canManageSecurity(1));
        $this->assertFalse((new UserAccess($this->user(1, [], ['me.security'])))->canManageSecurity(2));
    }

    public function testNobodyBansThemselvesEvenWithoutMeEdit(): void
    {
        $actor = $this->user(1, [], ['users.edit']);

        $this->assertFalse((new UserAccess($actor))->canBan($actor));
    }

    public function testBanningAnAdminNeedsManageAdmins(): void
    {
        $admin = $this->user(2, ['admin']);

        $this->assertFalse((new UserAccess($this->user(1, [], ['users.edit'])))->canBan($admin));
        $this->assertTrue((new UserAccess($this->user(1, [], ['users.edit', 'users.manage-admins'])))->canBan($admin));
        $this->assertTrue((new UserAccess($this->user(1, [], ['users.edit'])))->canBan($this->user(3, ['beta'])));
        $this->assertFalse((new UserAccess($this->user(1)))->canBan($this->user(3, ['beta'])));
    }

    public function testAdminGroupsAreAssignableOnlyByAdminManagers(): void
    {
        $manager = new UserAccess($this->user(1, [], ['users.edit', 'users.manage-admins']));
        $editor  = new UserAccess($this->user(1, [], ['users.edit']));

        $this->assertTrue($manager->canAssignGroup('superadmin'));
        $this->assertFalse($editor->canAssignGroup('admin'));
        $this->assertFalse($editor->canAssignGroup('superadmin'));
        $this->assertTrue($editor->canAssignGroup('beta'));
    }

    public function testGroupsEditorCannotAddOrRemoveAdminGroups(): void
    {
        $editor = new UserAccess($this->user(1, [], ['users.edit']));
        $target = $this->user(2, ['admin', 'beta']);

        // tries to add superadmin and to drop admin
        $result = $editor->allowedGroups($target, ['superadmin', 'developer']);

        $this->assertEqualsCanonicalizing(['developer', 'admin'], $result);
    }

    public function testGroupsAreUntouchedForAdminManagers(): void
    {
        $manager = new UserAccess($this->user(1, [], ['users.edit', 'users.manage-admins']));
        $target  = $this->user(2, ['admin']);

        $this->assertSame(['superadmin'], $manager->allowedGroups($target, ['superadmin']));
    }

    public function testUserManagementPermissionsCannotBeNewlyGrantedWithoutManageAdmins(): void
    {
        $editor = new UserAccess($this->user(1, [], ['users.edit']));
        $target = $this->user(2, [], ['users.list']);

        $this->assertFalse($editor->canAssignPermission($target, 'users.delete'));
        $this->assertTrue($editor->canAssignPermission($target, 'users.list'));
        $this->assertTrue($editor->canAssignPermission($target, 'me.edit'));

        $this->assertSame(
            ['users.list', 'me.edit'],
            $editor->allowedPermissions($target, ['users.list', 'users.delete', 'me.edit']),
        );
    }

    public function testAdminManagersCanGrantAnyPermission(): void
    {
        $manager = new UserAccess($this->user(1, [], ['users.edit', 'users.manage-admins']));

        $this->assertSame(['users.delete'], $manager->allowedPermissions($this->user(2), ['users.delete']));
    }

    public function testSelfAndProtectedAdminsAreNotSelectableForDeletion(): void
    {
        $editor = new UserAccess($this->user(1, [], ['users.delete']));

        $this->assertFalse($editor->canSelectForDeletion($this->user(1)));
        $this->assertFalse($editor->canSelectForDeletion($this->user(2, [], ['users.manage-admins'])));
        $this->assertTrue($editor->canSelectForDeletion($this->user(3)));

        $manager = new UserAccess($this->user(1, [], ['users.delete', 'users.manage-admins']));
        $this->assertTrue($manager->canSelectForDeletion($this->user(2, [], ['users.manage-admins'])));
    }
}
