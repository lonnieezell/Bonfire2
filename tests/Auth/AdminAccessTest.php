<?php

namespace Tests\Auth;

use CodeIgniter\Config\Factories;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class AdminAccessTest extends TestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace;

    public function testAdminsCanAccess()
    {
        $admin = $this->createUser();
        $admin->addGroup('superadmin');

        $guest = $this->createUser();
        $guest->addGroup('user');

        // Admins should be able to access the admin area.
        $response = $this->actingAs($admin)->get(ADMIN_AREA);
        $response->assertOK();
    }

    public function testCannotAccessAdminWithoutPermission()
    {
        $admin = $this->createUser();
        $admin->addGroup('superadmin');

        $guest = $this->createUser();
        $guest->addGroup('user');

        // User group doesn't have permission
        $response = $this->actingAs($guest)->get(ADMIN_AREA);

        $response->assertRedirectTo('/');
        $response->assertSessionHas('error', lang('Bonfire.notAuthorized'));
    }

    public function testCannotViewNavItemsWithoutPermission()
    {
        $config                  = config('AuthGroups');
        $config->matrix['admin'] = ['admin.access'];
        Factories::injectMock('config', 'AuthGroups', $config);

        $admin = $this->createUser();
        $admin->addGroup('admin');

        $response = $this->actingAs($admin)->get(ADMIN_AREA);

        // Should see 'Dashboard' link
        $response->assertSee('Dashboard');

        // Cannot see the User menus
        $response->assertDontSee('Users');
        $response->assertDontSee('Users');
        $response->assertDontSee('User Groups');

        // Cannot see General Admin settings
        $response->assertDontSee('Email');
        $response->assertDontSee('General');

        // Cannot see Consent settings
        $response->assertDontSee('Consent');

        // Cannot see Widget settings
        $response->assertDontSee('<span>Widgets');

        // Cannot see the Recycler area
        $response->assertDontSee('Recycler');
    }
}
