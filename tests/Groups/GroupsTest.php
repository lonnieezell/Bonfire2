<?php

namespace Tests\Groups;

use Bonfire\Users\User;
use CodeIgniter\Shield\Authorization\Groups;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class GroupsTest extends TestCase
{
    protected $refresh = true;
    protected $namespace;

    /**
     * @var User
     */
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser();
        $this->user->addGroup('superadmin');

        // Ensure superadmin always has Group permissions
        $auth                         = config('AuthGroups');
        $auth->matrix['superadmin'][] = 'groups.*';
        helper('setting');
        setting('AuthGroups.matrix', $auth->matrix);
    }

    public function testCanSeeUserList()
    {
        $result = $this->actingAs($this->user)
            ->get('/admin/settings/groups');

        // Page title
        $result->assertSee('User Groups');
        // See the list of groups
        $result->assertSee('Super Admin');
        $result->assertSee('Beta');
    }

    public function testCanSaveGroup()
    {
        $admin = $this->createUser();
        $admin->addGroup('superadmin');
        $result = $this->actingAs($admin)
            ->post('/admin/settings/groups/beta', [
                'title'       => 'Brave Soul',
                'description' => 'Tries broken things',
            ]);

        $result->assertSessionHas('message', lang('Bonfire.resourceSaved', ['group']));
        $groups = setting('AuthGroups.groups');
        $this->assertSame(['title' => 'Brave Soul', 'description' => 'Tries broken things'], $groups['beta']);
    }

    public function testCanSeeGroupPermissions()
    {
        $admin = $this->createUser();
        $admin->addGroup('superadmin');
        $result = $this->actingAs($admin)
            ->get('/admin/settings/groups/admin/permissions');

        // Page title
        $result->assertSee('admin.access');
    }

    public function testCanSaveGroupPermissions()
    {
        $groups = new Groups();
        $group  = $groups->info('admin');

        $this->assertTrue($group->can('beta.access'));

        $admin = $this->createUser();
        $admin->addGroup('superadmin');
        $result = $this->actingAs($admin)
            ->post('/admin/settings/groups/admin/permissions', [
                'permissions' => [
                    'admin.access',
                    'users.edit',
                ],
            ]);

        // Page title
        $result->assertRedirect();

        // Refresh the group
        $group = $groups->info('admin');

        $this->assertFalse($group->can('beta.access'));
    }
}
