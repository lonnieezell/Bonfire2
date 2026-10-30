<?php

namespace Tests\Users;

use Bonfire\Users\User;
use CodeIgniter\Config\Services;
use CodeIgniter\Test\TestResponse;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class EraseUsersTest extends TestCase
{
    protected $refresh = true;
    private User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();

        Services::resetSingle('personalData');
        Services::resetSingle('actions');

        $this->superadmin = $this->createUser();
        $this->superadmin->addGroup('superadmin');
    }

    protected function tearDown(): void
    {
        Services::resetSingle('personalData');
        Services::resetSingle('actions');

        parent::tearDown();
    }

    public function testErasesTheSelectedUsersAndRecordsTheRun(): void
    {
        $grace = $this->createUser(['username' => 'grace']);
        $ken   = $this->createUser(['username' => 'ken']);

        $response = $this->erase([$grace->id, $ken->id], $this->superadmin);

        $response->assertSessionHas('message');
        $this->seeInDatabase('users', ['id' => $grace->id, 'username' => 'deleted-user-' . $grace->id]);
        $this->seeInDatabase('users', ['id' => $ken->id, 'username' => 'deleted-user-' . $ken->id]);
        $this->seeInDatabase('action_runs', ['list' => 'users', 'action' => 'erase-users', 'user_id' => $this->superadmin->id, 'total' => 2, 'processed' => 2, 'status' => 'done']);
    }

    public function testAsksToTypeTheCountFirst(): void
    {
        $grace = $this->createUser(['username' => 'grace']);

        $this->actingAs($this->superadmin)->post(route_to('action', 'users', 'erase-users'), [
            csrf_token() => csrf_hash(),
            'selects'    => [$grace->id],
            'confirmed'  => '1',
        ])->assertSee('name="confirm_count"');

        $this->seeInDatabase('users', ['id' => $grace->id, 'username' => 'grace']);
    }

    public function testNobodyErasesThemselves(): void
    {
        $this->erase([$this->superadmin->id], $this->superadmin)->assertSessionHas('error');

        $this->seeInDatabase('users', ['id' => $this->superadmin->id, 'erased_at' => null]);
    }

    public function testErasingAdminsNeedsTheManageAdminsPermission(): void
    {
        $eraser = $this->createUser();
        $eraser->addPermission('admin.access', 'users.list', 'users.erase');
        $admin = $this->createUser(['username' => 'boss']);
        $admin->addGroup('superadmin');
        $plain = $this->createUser(['username' => 'grace']);

        $this->erase([$admin->id, $plain->id], $eraser);

        $this->seeInDatabase('users', ['id' => $admin->id, 'username' => 'boss']);
        $this->seeInDatabase('users', ['id' => $plain->id, 'username' => 'deleted-user-' . $plain->id]);
    }

    public function testOnlyUsersWithTheErasePermissionSeeOrRunIt(): void
    {
        $admin = $this->createUser();
        $admin->addGroup('admin');
        $grace = $this->createUser(['username' => 'grace']);

        $this->assertStringNotContainsString('erase-users', (string) $this->actingAs($admin)->get('admin/users')->response()->getBody());
        $this->assertStringContainsString('erase-users', (string) $this->actingAs($this->superadmin)->get('admin/users')->response()->getBody());

        $this->erase([$grace->id], $admin)->assertSessionHas('error', lang('Bonfire.notAuthorized'));
        $this->seeInDatabase('users', ['id' => $grace->id, 'username' => 'grace']);
    }

    /**
     * @param list<int> $ids
     */
    private function erase(array $ids, User $as): TestResponse
    {
        return $this->actingAs($as)->post(route_to('action', 'users', 'erase-users'), [
            csrf_token()    => csrf_hash(),
            'selects'       => $ids,
            'confirmed'     => '1',
            'confirm_count' => (string) count($ids),
        ]);
    }
}
