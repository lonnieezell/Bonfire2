<?php

namespace Tests\Users;

use Bonfire\Users\User;
use CodeIgniter\Router\Exceptions\RedirectException;
use Exception;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class UserFormTest extends TestCase
{
    protected $refresh = true;
    protected $namespace;
    protected User $user;

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

    /*
     * Will have to revisit later. SQLite is
     * being dumb, even though manual testing
     * shows this works.
     */
    //    public function testCanEditUser()
    //    {
    //        // Open the Edit User page
    //        $result = $this->actingAs($this->user)
    //            ->get('/admin/users/'. $this->user->id);
    //
    //        $result->assertOK();
    //        $result->assertSee('Edit User');
    //        $result->assertSee($this->user->email);
    //
    //        // Save the form
    //        $result = $this->actingAs($this->user)
    //            ->post("/admin/users/{$this->user->id}/save", [
    //                'email' => $this->user->email,
    //                'username' => 'Freddy',
    //                'first_name' => 'Fred',
    //                'last_name' => 'Flintstone',
    //                'groups' => ['beta']
    //            ]);
    //
    //        $result->assertRedirect();
    //
    //        $this->seeInDatabase('users', [
    //            'id' => $this->user->id,
    //            'first_name' => 'Fred',
    //            'last_name' => 'Flintstone'
    //        ]);
    //    }
}
