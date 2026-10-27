<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Groups\Libraries;

use CodeIgniter\Shield\Authorization\Groups;

class GroupPermissions
{
    /**
     * How many of the defined permissions (AuthGroups.permissions) each group grants,
     * wildcards like `users.*` expanded.
     *
     * @param list<string> $aliases
     *
     * @return array<string, int>
     */
    public static function counts(array $aliases): array
    {
        $permissions = array_keys(setting('AuthGroups.permissions') ?? []);
        $groups      = new Groups();
        $counts      = [];

        foreach ($aliases as $alias) {
            $group          = $groups->info($alias);
            $counts[$alias] = $group === null ? 0 : count(array_filter($permissions, $group->can(...)));
        }

        return $counts;
    }

    /**
     * Permissions grouped by their prefix: `users.edit` goes under `users`.
     *
     * @param array<string, string> $permissions name => description
     *
     * @return array<string, array<string, string>>
     */
    public static function byPrefix(array $permissions): array
    {
        $grouped = [];

        foreach ($permissions as $permission => $description) {
            $grouped[explode('.', $permission)[0]][$permission] = $description;
        }

        return $grouped;
    }
}
