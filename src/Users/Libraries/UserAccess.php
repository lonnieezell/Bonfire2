<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Users\Libraries;

use Bonfire\Users\User;

/**
 * Answers "may the acting user do this to that user?" for the Users admin.
 * The controller enforces these answers and the views use them to disable
 * controls, so the two cannot drift apart.
 */
final readonly class UserAccess
{
    private const ADMIN_GROUPS = ['admin', 'superadmin'];

    public function __construct(private User $actor)
    {
    }

    public static function forCurrentUser(): self
    {
        return new self(auth()->user());
    }

    private function isSelf(?int $userId): bool
    {
        return $userId !== null && $this->actor->id === $userId;
    }

    public function canOpenEditForm(?int $userId): bool
    {
        return $this->canEditUsers()
            || ($this->isSelf($userId) && $this->actor->can('me.edit', 'me.security'));
    }

    public function canSave(?int $userId): bool
    {
        return $this->canEditUsers()
            || ($this->isSelf($userId) && $this->actor->can('me.edit'));
    }

    public function canManageSecurity(?int $userId): bool
    {
        return $this->canEditUsers()
            || ($this->isSelf($userId) && $this->actor->can('me.security'));
    }

    public function canEditUsers(): bool
    {
        return $this->actor->can('users.edit');
    }

    public function canManageAdmins(): bool
    {
        return $this->actor->can('users.manage-admins');
    }

    /**
     * Nobody bans themselves, and only admin managers ban admins.
     */
    public function canBan(User $target): bool
    {
        return $this->canEditUsers()
            && ! $this->isSelf($target->id)
            && (! $target->inGroup(...self::ADMIN_GROUPS) || $this->canManageAdmins());
    }

    public function canAssignGroup(string $group): bool
    {
        return $this->canManageAdmins() || ! in_array($group, self::ADMIN_GROUPS, true);
    }

    /**
     * The groups to store for $target after $actor asked for $requested.
     * Admin groups are neither added nor taken away unless the actor
     * manages admins.
     *
     * @param list<string> $requested
     *
     * @return list<string>
     */
    public function allowedGroups(User $target, array $requested): array
    {
        if ($this->canManageAdmins()) {
            return $requested;
        }

        $groups = array_filter(
            $requested,
            static fn (string $group): bool => ! in_array($group, self::ADMIN_GROUPS, true) || $target->inGroup($group),
        );

        foreach ($target->getGroups() ?? [] as $group) {
            if (in_array($group, self::ADMIN_GROUPS, true) && ! in_array($group, $groups, true)) {
                $groups[] = $group;
            }
        }

        return array_values($groups);
    }

    /**
     * User-management permissions can only be newly granted by admin managers.
     */
    public function canAssignPermission(User $target, string $permission): bool
    {
        return $this->canManageAdmins()
            || $target->hasPermission($permission)
            || explode('.', $permission)[0] !== 'users';
    }

    /**
     * @param list<string> $requested
     *
     * @return list<string>
     */
    public function allowedPermissions(User $target, array $requested): array
    {
        return array_values(array_filter(
            $requested,
            fn (string $permission): bool => $this->canAssignPermission($target, $permission),
        ));
    }

    public function canSelectForDeletion(User $target): bool
    {
        return ! $this->isSelf($target->id)
            && ($this->canManageAdmins() || ! $target->can('users.manage-admins'));
    }
}
