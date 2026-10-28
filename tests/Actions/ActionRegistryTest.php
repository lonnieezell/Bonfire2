<?php

namespace Tests\Actions;

use Bonfire\Actions\ActionRegistry;
use Bonfire\Actions\Placement;
use Tests\Support\Actions\TouchUsers;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class ActionRegistryTest extends TestCase
{
    public function testActionsAreFoundByNameOnTheirList(): void
    {
        $registry = new ActionRegistry();
        $touch    = new TouchUsers();

        $registry->for('users')->add($touch);

        $this->assertSame('touch-users', $touch->name());
        $this->assertSame($touch, $registry->for('users')->find('touch-users'));
        $this->assertNull($registry->for('users')->find('nothing'));
        $this->assertNull($registry->for('groups')->find('touch-users'));
    }

    public function testRowAndBulkListOnlyPermittedActionsInTheirPlacement(): void
    {
        $bulkOnly              = new class () extends TouchUsers {};
        $bulkOnly->placement   = Placement::Bulk;
        $forbidden             = new class () extends TouchUsers {};
        $forbidden->permission = 'users.delete';

        $list = (new ActionRegistry())->for('users')->add(new TouchUsers())->add($bulkOnly)->add($forbidden);

        $user = $this->createUser();
        $user->addPermission('users.edit');

        $this->assertSame([TouchUsers::class], array_map(get_class(...), $list->row($user)));
        $this->assertCount(2, $list->bulk($user));
    }

    public function testRowLeavesOutActionsTheRecordDoesNotAllow(): void
    {
        $list = (new ActionRegistry())->for('users')->add(new TouchUsers());
        $user = $this->createUser();
        $user->addPermission('users.edit');

        // TouchUsers refuses the actor's own record
        $this->assertSame([], $list->row($user, $user));
        $this->assertCount(1, $list->row($user, $this->createUser()));
    }

    public function testUsersModuleRegistersItsDeleteAction(): void
    {
        $user = $this->createUser();
        $user->addGroup('superadmin');

        // Modules register in initAdmin(), which runs while an admin page is served
        $this->actingAs($user)->get(route_to('user-list'));

        $this->assertNotNull(service('actions')->for('users')->find('delete-users'));
    }
}
