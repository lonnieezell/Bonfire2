<?php

namespace Tests\Recycler;

use Bonfire\Recycler\Libraries\RecyclableResource;
use Bonfire\Recycler\Libraries\Recycler;
use Bonfire\Users\Models\UserModel;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class RecyclerRegistryTest extends TestCase
{
    protected $refresh = true;

    protected function tearDown(): void
    {
        service('settings')->forget('Recycler.resources');
        service('settings')->forget('Recycler.defaultResource');

        parent::tearDown();
    }

    public function testUsersModuleRegistersUsers()
    {
        $user = $this->createUser();
        $user->addGroup('superadmin');

        // Modules register in initAdmin(), which runs while an admin page is served
        $this->actingAs($user)->get(route_to('recycler'));

        $users = service('recycler')->find('users');

        $this->assertNotNull($users);
        $this->assertSame(['username', 'first_name', 'last_name', 'email'], $users->columns());
    }

    public function testRegisteredResourceCanBeFound()
    {
        $recycler = new Recycler();
        $recycler->register('users', 'Users', UserModel::class, ['username']);

        $this->assertSame(['users'], array_keys($recycler->resources()));
        $this->assertSame(['username'], $recycler->find('users')->columns());
    }

    public function testUnknownResourceIsNotFound()
    {
        $recycler = new Recycler();
        $recycler->register('users', 'Users', UserModel::class, ['username']);

        $this->assertNull($recycler->find('nothing'));
    }

    public function testNoAliasFindsTheDefaultResource()
    {
        $recycler = new Recycler();
        $recycler->register('users', 'Users', UserModel::class, ['username']);

        setting('Recycler.defaultResource', 'users');
        $this->assertSame('users', $recycler->find(null)->alias());
        $this->assertSame('users', $recycler->find('')->alias());
    }

    public function testAppConfigCanChangeOnlyPartOfAResource()
    {
        $recycler = new Recycler();
        $recycler->register('users', 'Users', UserModel::class, ['username']);

        setting('Recycler.resources', ['users' => ['label' => 'People']]);

        $users = $recycler->find('users');
        $this->assertInstanceOf(RecyclableResource::class, $users);

        $this->assertSame('People', $users->label());
        $this->assertSame(['username'], $users->columns());
    }

    public function testAppConfigStillAddsAndOverridesResources()
    {
        $recycler = new Recycler();
        $recycler->register('users', 'Users', UserModel::class, ['username']);

        setting('Recycler.resources', [
            'users'  => ['label' => 'People', 'model' => UserModel::class, 'columns' => ['email']],
            'others' => ['label' => 'Others', 'model' => UserModel::class, 'columns' => ['id']],
        ]);

        $this->assertSame(['users', 'others'], array_keys($recycler->resources()));
        $this->assertSame(['email'], $recycler->find('users')->columns());
    }
}
