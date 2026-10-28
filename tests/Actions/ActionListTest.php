<?php

namespace Tests\Actions;

use Bonfire\Actions\ActionList;
use Bonfire\Actions\RecordSource;
use Tests\Support\Actions\TouchUsers;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class ActionListTest extends TestCase
{
    public function testANonDeleteBulkActionMakesRowsSelectable(): void
    {
        $list   = (new ActionList('users'))->add(new TouchUsers());
        $editor = $this->createUser();
        $editor->addPermission('users.edit');

        $this->assertTrue($list->selectable($editor));
        $this->assertFalse($list->selectable($this->createUser()));
    }

    public function testARecordRejectedByAllowsIsNotSelectable(): void
    {
        $list = (new ActionList('users'))->add(new TouchUsers());
        $user = $this->createUser();
        $user->addPermission('users.edit');

        // TouchUsers refuses the actor's own record
        $this->assertFalse($list->selectable($user, $user));
        $this->assertTrue($list->selectable($user, $this->createUser()));
    }

    public function testRowActionsUseTheRecordsShownInsteadOfLoadingEach(): void
    {
        $source = new class () implements RecordSource {
            public int $loads = 0;

            public function find(array $ids): array
            {
                $this->loads++;

                return [];
            }
        };
        $list = (new ActionList('users'))->records($source)->add(new TouchUsers());
        $user = $this->createUser();
        $user->addPermission('users.edit');
        $other = $this->createUser();

        $list->showing([$user, $other]);

        $this->assertSame([], $list->rowFor($user, $user->id));
        $this->assertCount(1, $list->rowFor($user, $other->id));
        $this->assertSame(0, $source->loads);

        // A record the page didn't hand over is loaded
        $this->assertSame([], $list->rowFor($user, 999));
        $this->assertSame(1, $source->loads);
    }
}
