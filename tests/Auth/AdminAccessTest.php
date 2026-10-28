<?php

namespace Tests\Auth;

use CodeIgniter\Config\Factories;
use CodeIgniter\HTTP\Exceptions\RedirectException;
use CodeIgniter\Test\DatabaseTestTrait;
use Exception;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class AdminAccessTest extends TestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace;

    /**
     * @throws Exception
     * @throws RedirectException
     */
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

    /**
     * @throws Exception
     * @throws RedirectException
     */
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

    /**
     * @throws Exception
     * @throws RedirectException
     */
    public function testCannotViewNavItemsWithoutPermission()
    {
        $config                  = config('AuthGroups');
        $config->matrix['admin'] = ['admin.access'];
        Factories::injectMock('config', 'AuthGroups', $config);

        $admin = $this->createUser();
        $admin->addGroup('admin');

        $response = $this->actingAs($admin)->get(ADMIN_AREA);

        // The dashboard's widget palette names widgets too, so look at the sidebar only
        preg_match('#<aside id="sidebar".*?</aside>#s', (string) $response->response()->getBody(), $match);
        $sidebar = $match[0] ?? '';

        // Should see 'Dashboard' link
        $this->assertStringContainsString('Dashboard', $sidebar);

        // Cannot see the User menus
        $this->assertStringNotContainsString('href="admin/users"', $sidebar);
        $this->assertStringNotContainsString('User Groups', $sidebar);

        // Cannot see General Admin settings
        $this->assertStringNotContainsString('Email', $sidebar);
        $this->assertStringNotContainsString('General', $sidebar);

        // Cannot see Consent settings
        $this->assertStringNotContainsString('Consent', $sidebar);

        // Cannot see Widget settings
        $this->assertStringNotContainsString('<span>Widgets', $sidebar);

        // Cannot see the Recycler area
        $this->assertStringNotContainsString('Recycler', $sidebar);
    }
}
