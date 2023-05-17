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

/**
 * Provides card widget of groups
 */
class GroupsCardWidget
{
    protected $viewPrefix = 'Bonfire\Groups\Views\\';

    /**
     * Displays the list of most recent logins
     */
    public function listGroups()
    {
        if (! auth()->user()->can('groups.settings')) {
            return '';
        }

        $groups = setting('AuthGroups.groups');
        asort($groups);

        // Find the number of users in this group
        foreach ($groups as $alias => &$group) {
            $group['user_count'] = db_connect()
                ->table('auth_groups_users')
                ->where('group', $alias)
                ->countAllResults(true);
        }

        return view($this->viewPrefix . '_groups_card', [
            'groups' => $groups,
        ]);
    }
}
