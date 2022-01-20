<?php

namespace Tests\Bonfire\Auth;

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

    public function testCannotAccessAdminWithoutPermission()
    {
        $admin = $this->createUser();
        $admin->addGroup('superadmin');

        $guest = $this->createUser();
        $guest->addGroup('user');

        // Admins should be able to access the admin area.
        $response = $this->actingAs($admin)->get(ADMIN_AREA);
        $response->assertOK();

        // User group doesn't have permission
        $response = $this->actingAs($guest)->get(ADMIN_AREA);
        $response->assertRedirectTo('/');
        $response->assertSessionHas('error', lang('Bonfire.notAuthorized'));
    }
}
