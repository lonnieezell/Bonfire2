<?php

namespace Tests\Users;

use Bonfire\Users\User;
use CodeIgniter\HTTP\Exceptions\RedirectException;
use Exception;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class UserFormTest extends TestCase
{
    protected $refresh = true;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser();
        $this->user->addGroup('superadmin');

        setting('Auth.actions', ['login' => null, 'register' => null]);
    }

    /**
     * @throws Exception
     * @throws RedirectException
     */
    public function testCanSeeUserList()
    {
        $result = $this->actingAs($this->user)
            ->get('/admin/users');

        $result->assertSee($this->user->email);
    }

    public function testCanEditUser()
    {
        // Open the Edit User page
        $result = $this->actingAs($this->user)
            ->get('/admin/users/' . $this->user->id);

        $result->assertOK();
        $result->assertSee('Edit User');
        $result->assertSee($this->user->email);

        // Save the form
        $result = $this->actingAs($this->user)
            ->post("/admin/users/{$this->user->id}/save", [
                'id'         => $this->user->id,
                'email'      => $this->user->email,
                'username'   => 'Freddy',
                'first_name' => 'Fred',
                'last_name'  => 'Flintstone',
                'groups'     => ['beta'],
            ]);

        $result->assertRedirect();

        $this->seeInDatabase('users', [
            'id'         => $this->user->id,
            'first_name' => 'Fred',
            'last_name'  => 'Flintstone',
        ]);
    }

    public function testCanChangePassword()
    {
        $result = $this->actingAs($this->user)
            ->post("/admin/users/{$this->user->id}/changePassword", [
                'password'     => 'a-Very-Long-Passw0rd-42!',
                'pass_confirm' => 'a-Very-Long-Passw0rd-42!',
            ]);

        $result->assertRedirectTo(site_url("admin/users/{$this->user->id}/security"));
    }
}
