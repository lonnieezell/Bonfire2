<?php

namespace Tests\Recycler;

use Bonfire\Users\Models\UserModel;
use Bonfire\Users\User;
use Tests\Support\Models\RecyclerHooksModel;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class RecyclerTest extends TestCase
{
    protected $refresh = true;
    private User $admin;
    private UserModel $users;

    protected function setUp(): void
    {
        parent::setUp();

        // Other tests may leave the site in offline mode, which redirects users without site.viewOffline
        setting('Site.siteOnline', true);

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
            ->get(route_to('recycler'));

        $response->assertOK();
        $response->assertSee($user1->first_name);
        $response->assertDontSee($user2->first_name);
    }

    public function testRestore()
    {
        $user1 = $this->createUser();
        $this->users->delete($user1->id);

        $this->actingAs($this->admin)
            ->get(route_to('recycler-restore', 'users', $user1->id));

        $this->seeInDatabase('users', [
            'id'         => $user1->id,
            'deleted_at' => null,
        ]);
    }

    public function testPurge()
    {
        $user1 = $this->createUser();
        $this->users->delete($user1->id);

        $this->actingAs($this->admin)
            ->get(route_to('recycler-purge', 'users', $user1->id));

        $this->dontSeeInDatabase('users', [
            'id' => $user1->id,
        ]);
    }

    private function userWithoutRecyclerAccess(): User
    {
        $user = $this->createUser();
        $user->addPermission('admin.access');

        return $user;
    }

    public function testIndexNeedsRecyclerPermission()
    {
        $deleted = $this->createUser();
        $this->users->delete($deleted->id);

        $response = $this->actingAs($this->userWithoutRecyclerAccess())
            ->get(route_to('recycler'));

        $response->assertRedirectTo(ADMIN_AREA);
        $response->assertDontSee($deleted->first_name);
    }

    public function testRestoreNeedsRecyclerPermission()
    {
        $deleted = $this->createUser();
        $this->users->delete($deleted->id);

        $this->actingAs($this->userWithoutRecyclerAccess())
            ->get(route_to('recycler-restore', 'users', $deleted->id))
            ->assertRedirectTo(ADMIN_AREA);

        $this->assertNotNull(db_connect()->table('users')->where('id', $deleted->id)->get()->getRow()->deleted_at);
    }

    public function testPurgeNeedsRecyclerPermission()
    {
        $deleted = $this->createUser();
        $this->users->delete($deleted->id);

        $this->actingAs($this->userWithoutRecyclerAccess())
            ->get(route_to('recycler-purge', 'users', $deleted->id))
            ->assertRedirectTo(ADMIN_AREA);

        $this->seeInDatabase('users', ['id' => $deleted->id]);
    }

    public function testUnknownResourceIsNotListed()
    {
        $response = $this->actingAs($this->admin)
            ->get(route_to('recycler') . '?r=nothing');

        $response->assertRedirect();
        $this->assertSame(lang('Bonfire.resourceNotFound', [lang('Recycler.resourceType')]), session('error'));
    }

    public function testUnknownResourceIsNotRestoredOrPurged()
    {
        $deleted = $this->createUser();
        $this->users->delete($deleted->id);

        foreach (['recycler-restore', 'recycler-purge'] as $route) {
            $response = $this->actingAs($this->admin)
                ->get(route_to($route, 'nothing', $deleted->id));

            $response->assertRedirect();
            $this->assertSame(lang('Bonfire.resourceNotFound', [lang('Recycler.resourceType')]), session('error'));
        }

        $this->seeInDatabase('users', ['id' => $deleted->id]);
    }

    public function testRestoreOfUnknownRecordReportsNothing()
    {
        $response = $this->actingAs($this->admin)
            ->get(route_to('recycler-restore', 'users', 999999));

        $response->assertRedirect();
        $this->assertNull(session('message'));
    }

    public function testCustomHooksAreUsedByTheController()
    {
        service('recycler')->register('hooks', 'Hooks', RecyclerHooksModel::class, ['username']);
        RecyclerHooksModel::$calls = [];

        $deleted = $this->createUser();
        $this->users->delete($deleted->id);

        $this->actingAs($this->admin)->get(route_to('recycler') . '?r=hooks');
        $this->actingAs($this->admin)->get(route_to('recycler-restore', 'hooks', $deleted->id));
        $this->actingAs($this->admin)->get(route_to('recycler-purge', 'hooks', $deleted->id));

        $this->assertSame(['query', "restore {$deleted->id}", "purge {$deleted->id}"], RecyclerHooksModel::$calls);
    }
}
