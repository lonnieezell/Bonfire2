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
use Bonfire\Widgets\Types\Charts\ChartsItem;
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
            'bgColor' => 'bg-teal',
            'title'   => 'User Groups',
            'value'   => count(setting('AuthGroups.groups')),
            'id'      => 'userGroups643',
            'url'     => ADMIN_AREA . '/settings/groups',
            'faIcon'  => 'fa fa-users',
        ]);
        // alternative way of retrieving group number (groups "in use"):
        // $statsItem->addValueByFreeQuery('SELECT COUNT(DISTINCT "group") AS groups FROM auth_groups_users;');
        $widgets->widget('stats')->collection('stats')->addItem($statsItem);

        $statsItem = new ChartsItem([
            'title'    => 'User classification by group',
            'type'     => 'line',
            'id'       => 'usersByGroupLine123',
            'cssClass' => 'col-6',
        ]);
        $statsItem->addDataset('auth_groups_users', 'group', 'user_id');
        $widgets->widget('charts')->collection('charts')->addItem($statsItem);

        $statsItem1 = new ChartsItem([
            'title'    => 'User classification by group',
            'type'     => 'bar',
            'id'       => 'usersByGroupBar345',
            'cssClass' => 'col-6',
        ]);
        $statsItem1->addDataset('auth_groups_users', 'group', 'user_id');
        $widgets->widget('charts')->collection('charts')->addItem($statsItem1);

        $statsItem2 = new ChartsItem([
            'title'    => 'User classification by group',
            'type'     => 'doughnut',
            'id'       => 'usersByGroupDou654',
            'cssClass' => 'col-3',
        ]);
        $statsItem2->addDataset('auth_groups_users', 'group', 'user_id');
        $widgets->widget('charts')->collection('charts')->addItem($statsItem2);

        $statsItem3 = new ChartsItem([
            'title'    => 'User classification by group',
            'type'     => 'pie',
            'id'       => 'usersByGroupPie223',
            'cssClass' => 'col-3',
        ]);
        $statsItem3->addDataset('auth_groups_users', 'group', 'user_id');
        $widgets->widget('charts')->collection('charts')->addItem($statsItem3);

        $statsItem4 = new ChartsItem([
            'title'    => 'User classification by group',
            'type'     => 'polarArea',
            'id'       => 'usersByGroupPiePolar645',
            'cssClass' => 'col-3',
        ]);
        $statsItem4->addDataset('auth_groups_users', 'group', 'user_id');
        $widgets->widget('charts')->collection('charts')->addItem($statsItem4);
    }
}
