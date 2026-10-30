<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Groups;

use Bonfire\Core\BaseModule;
use Bonfire\Menus\MenuItem;
use Bonfire\Widgets\Types\Stats\StatsItem;

/**
 * User Module setup
 */
class Module extends BaseModule
{
    /**
     * Setup our admin area needs.
     */
    public function initAdmin()
    {
        // Settings menu for sidebar
        $sidebar = service('menus');
        $item    = new MenuItem([
            'title'           => lang('Groups.groupsModTitle'),
            'namedRoute'      => 'user-group-settings',
            'fontAwesomeIcon' => 'fas fa-users',
            'permission'      => 'groups.settings',
        ]);
        $sidebar->menu('sidebar')->collection('settings')->addItem($item);

        // Settings widgets stats on dashboard
        $widgets   = service('widgets');
        $statsItem = new StatsItem([
            'bgColor'    => 'bg-teal',
            'title'      => lang('Groups.groupsModTitle'),
            'value'      => count(setting('AuthGroups.groups')),
            'id'         => 'userGroups643',
            'url'        => ADMIN_AREA . '/settings/groups',
            'faIcon'     => 'fa fa-users',
            'permission' => 'groups.settings',
            'module'     => lang('Groups.groupsModTitle'),
        ]);
        // alternative way of retrieving group number (groups "in use"):
        // $statsItem->addValueByFreeQuery('SELECT COUNT(DISTINCT "group") AS groups FROM auth_groups_users;');
        $widgets->widget('stats')->collection('stats')->addItem($statsItem);
    }
}
