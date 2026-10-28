<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Users\Actions;

use Bonfire\Actions\DeleteAction;
use Bonfire\Users\Libraries\UserAccess;
use Bonfire\Users\Models\UserModel;
use Bonfire\Users\User;
use RuntimeException;

/**
 * Deletes users (softly, so they land in the Recycler). Admins can't delete
 * themselves, and only admin managers can delete users who manage admins.
 */
class DeleteUsers extends DeleteAction
{
    public string $permission = 'users.delete';

    public function allows(User $actor, object $record): bool
    {
        return (new UserAccess($actor))->canSelectForDeletion($record);
    }

    protected function delete(array $records): void
    {
        $users = model(UserModel::class);

        if (! $users->delete(array_map(static fn (User $user) => $user->id, $records))) {
            throw new RuntimeException(implode(' ', $users->errors()));
        }
    }

    protected function noun(): string
    {
        return lang('Users.usersAccusative');
    }
}
