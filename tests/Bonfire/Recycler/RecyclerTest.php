<?php

namespace Tests\Bonfire\Recycler;

use App\Entities\User;
use App\Models\UserModel;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class RecyclerTest extends TestCase
{
    protected $refresh = true;
    protected $namespace;

    /**
     * @var User
     */
    protected $admin;

    /**
     * @var UserModel
     */
    protected $users;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createUser();
        $this->admin->addGroup('superadmin');

        $this->users = new UserModel();
    }

    public function testIndexShowsDeletedUsers()
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();

        $this->users->delete($user1->id);

        $response = $this->actingAs($this->admin)
            ->get(ADMIN_AREA . '/recycler');

        $response->assertOK();
        $response->assertSee($user1->first_name);
        $response->assertDontSee($user2->first_name);
    }

    public function testRestore()
    {
        $user1 = $this->createUser();
        $this->users->delete($user1->id);

        $result = $this->actingAs($this->admin)
            ->get(ADMIN_AREA . '/recycler/restore/users/' . $user1->id);

        $this->seeInDatabase('users', [
            'id'         => $user1->id,
            'deleted_at' => null,
        ]);
    }

    public function testPurge()
    {
        $user1 = $this->createUser();
        $this->users->delete($user1->id);

        $result = $this->actingAs($this->admin)
            ->get(ADMIN_AREA . '/recycler/purge/users/' . $user1->id);

        $this->dontSeeInDatabase('users', [
            'id' => $user1->id,
        ]);
    }
}
