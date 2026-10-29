<?php

namespace Tests\Commands;

use CodeIgniter\Test\StreamFilterTrait;
use Tests\Support\Packages\Courier\Config\Registrar as CourierRegistrar;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class PermissionsTest extends TestCase
{
    use StreamFilterTrait;

    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();

        helper('setting');
        setting()->forget('AuthGroups.matrix');
        setting()->forget('AuthGroups.appliedGrants');
        setting()->forget('AuthGroups.groups');

        $courier = CourierRegistrar::AuthGroups();
        config('AuthGroups')->permissions += $courier['permissions'];
        config('AuthGroups')->moduleGrants = $courier['moduleGrants'];
    }

    public function testGrantsAreAddedToAnUnstoredMatrix(): void
    {
        command('bonfire:permissions');

        $matrix = setting('AuthGroups.matrix');

        $this->assertContains('courier.*', $matrix['superadmin']);
        $this->assertContains('courier.send', $matrix['admin']);
        $this->assertContains('admin.access', $matrix['admin']);
    }

    public function testGrantsAreAddedToAStoredMatrix(): void
    {
        setting('AuthGroups.matrix', ['superadmin' => ['admin.*'], 'admin' => ['users.list']]);

        command('bonfire:permissions');

        $this->assertSame(
            ['superadmin' => ['admin.*', 'courier.*'], 'admin' => ['users.list', 'courier.send']],
            setting('AuthGroups.matrix'),
        );
    }

    public function testASecondRunChangesNothing(): void
    {
        command('bonfire:permissions');
        $matrix = setting('AuthGroups.matrix');

        command('bonfire:permissions');

        $this->assertSame($matrix, setting('AuthGroups.matrix'));
    }

    public function testWithNothingToGrantTheMatrixKeepsFollowingTheConfig(): void
    {
        config('AuthGroups')->moduleGrants = [];

        command('bonfire:permissions');
        config('AuthGroups')->matrix['beta'][] = 'courier.send';

        $this->assertContains('courier.send', setting('AuthGroups.matrix')['beta']);
    }

    public function testAGrantTheMatrixAlreadyHasLeavesItFollowingTheConfig(): void
    {
        config('AuthGroups')->moduleGrants = ['courier' => ['admin' => ['admin.access']]];

        command('bonfire:permissions');
        config('AuthGroups')->matrix['beta'][] = 'courier.send';

        $this->assertContains('courier.send', setting('AuthGroups.matrix')['beta']);
    }

    public function testAGrantAnAdminRemovedIsNotAddedBack(): void
    {
        command('bonfire:permissions');
        $matrix          = setting('AuthGroups.matrix');
        $matrix['admin'] = array_values(array_diff($matrix['admin'], ['courier.send']));
        setting('AuthGroups.matrix', $matrix);

        command('bonfire:permissions');

        $this->assertNotContains('courier.send', setting('AuthGroups.matrix')['admin']);
    }

    public function testAGrantForAnUnknownGroupIsSkippedAndReported(): void
    {
        config('AuthGroups')->moduleGrants['courier']['ghosts'] = ['courier.send'];

        command('bonfire:permissions');

        $this->assertArrayNotHasKey('ghosts', setting('AuthGroups.matrix'));
        $this->assertArrayNotHasKey('ghosts', setting('AuthGroups.groups'));
        $this->assertStringContainsString('ghosts', $this->getStreamFilterBuffer());
    }

    public function testUsersCanUseTheirGroupsNewGrants(): void
    {
        $user = $this->createUser();
        $user->addGroup('admin');

        command('bonfire:permissions');

        $this->assertTrue($user->can('courier.send'));
    }
}
