<?php

namespace Tests\Groups;

use Bonfire\Users\User;
use CodeIgniter\Shield\Authorization\Groups;
use CodeIgniter\Shield\Entities\Group;
use Tests\Support\SubmitsForms;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class GroupsTest extends TestCase
{
    use SubmitsForms;

    protected $refresh = true;
    private User $user;

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
        // Saving a group leaves its new title in the settings cache.
        setting('AuthGroups.groups', $auth->groups);
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
        $this->assertInstanceof(Group::class, $group);

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
        $this->assertInstanceof(Group::class, $group);

        // Refresh the group
        $group = $groups->info('admin');
        $this->assertInstanceof(Group::class, $group);

        $this->assertFalse($group->can('beta.access'));
    }

    public function testListRowsShowAliasDefaultBadgeAndCounts(): void
    {
        setting('AuthGroups.defaultGroup', 'beta');
        $this->createUser()->addGroup('beta');

        $html = $this->actingAs($this->user)->get('/admin/settings/groups')->getBody();

        $this->assertMatchesRegularExpression(
            '#>Beta User</a>\s*<span class="bf-pill">Default</span>.*?<td class="bf-mono">beta</td>\s*<td class="text-end">1</td>\s*<td class="text-end">1</td>#s',
            $html,
        );
    }

    public function testGroupDetailsRoundTripThroughTheForm(): void
    {
        $html   = $this->actingAs($this->user)->get('/admin/settings/groups/beta')->getBody();
        $fields = $this->formFields($html, site_url(ADMIN_AREA . '/settings/groups/beta'));

        $this->actingAs($this->user)->post('/admin/settings/groups/beta', $fields)->assertSessionMissing('errors');

        $this->assertSame(config('AuthGroups')->groups['beta'], setting('AuthGroups.groups')['beta']);
    }

    public function testGroupPermissionsRoundTripThroughTheForm(): void
    {
        $before = $this->effectivePermissions('admin');

        $html   = $this->actingAs($this->user)->get('/admin/settings/groups/admin/permissions')->getBody();
        $fields = $this->formFields($html, site_url(ADMIN_AREA . '/settings/groups/admin/permissions'));
        $this->actingAs($this->user)->post('/admin/settings/groups/admin/permissions', $fields)->assertRedirect();

        $this->assertSame($before, $this->effectivePermissions('admin'));
        $this->assertMatchesRegularExpression('#<h2>Widgets</h2>.*?name="permissions\[\]" value="widgets.settings"[^>]* checked>#s', $html);
    }

    /**
     * @return list<string>
     */
    private function effectivePermissions(string $alias): array
    {
        $group = (new Groups())->info($alias);

        return array_values(array_filter(array_keys(setting('AuthGroups.permissions')), $group->can(...)));
    }
}
