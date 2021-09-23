<?php

namespace Tests\Bonfire\Groups;

use App\Entities\User;
use Tests\Support\TestCase;

class GroupsTest extends TestCase
{
    protected $refresh = true;
    protected $namespace = null;

    /**
     * @var User
     */
    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser();
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
                           'title' => 'Brave Soul',
                           'description' => 'Tries broken things',
                       ]);

       $result->assertSessionHas('message', lang('Bonfire.resourceSaved', ['group']));
       $groups = setting('AuthGroups.groups');
       $this->assertEquals(['title' => 'Brave Soul', 'description' => 'Tries broken things'], $groups['beta']);
    }
}
