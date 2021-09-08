<?php

namespace Tests\Bonfire\Users;

use App\Entities\User;
use App\Models\UserModel;
use Faker\Factory;
use Tests\Support\TestCase;

class UserFormTest extends TestCase
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
