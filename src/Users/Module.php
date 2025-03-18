<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Users;

use Bonfire\Core\BaseModule;
use Bonfire\Menus\MenuItem;
use Bonfire\Users\Models\UserModel;
use Bonfire\Widgets\Types\Stats\StatsItem;
use CodeIgniter\View\Table;

class Module extends BaseModule
{
    /**
     * Setup our admin area needs.
     */
    public function initAdmin()
    {
        // Add to the Content menu
        $sidebar = service('menus');
        $item    = new MenuItem([
            'title'           => lang('Users.usersModTitle'),
            'namedRoute'      => 'user-list',
            'fontAwesomeIcon' => 'fas fa-users',
            'permission'      => 'users.view',
        ]);
        $sidebar->menu('sidebar')->collection('content')->addItem($item);

        // Add Users Settings
        $item = new MenuItem([
            'title'           => lang('Users.usersModTitle'),
            'namedRoute'      => 'user-settings',
            'fontAwesomeIcon' => 'fas fa-user-cog',
            'permission'      => 'users.settings',
        ]);
        $sidebar->menu('sidebar')->collection('settings')->addItem($item);

        // Settings widgets stats on dashboard
        $widgets   = service('widgets');
        $statsItem = new StatsItem([
            'bgColor' => 'bg-danger',
            'title'   => 'Users in Recycler',
            // assigning param directly possible, but it always executes the query, even if not on dashboard or the stats item not enabled, not optimal
            // 'value'   => (new UserModel())->onlyDeleted()->countAllResults(),
            'id'     => 'usersInRecycler693',
            'url'    => ADMIN_AREA . '/tools/recycler?r=users',
            'faIcon' => 'fa fa-users',
        ]);
        // better way of retrieving deleted users, executes only when widget is displayed:
        $statsItem->addValue('users', 'deleted_at IS NOT NULL');
        // or, the following can be used with more complicated queries:
        // $statsItem->addValueByFreeQuery('SELECT COUNT(*) AS count FROM users WHERE deleted_at IS NOT NULL;');
        $widgets->widget('stats')->collection('stats')->addItem($statsItem);

        $widgets    = service('widgets');
        $statsItem1 = new StatsItem([
            'bgColor' => 'bg-purple',
            'title'   => 'Users by Group',
            'id'      => 'usersExpTable693',
            'value'   => $this->buildTableUsersByGroup('usersExpTable693'),
            'url'     => ADMIN_AREA . '/users',
            'faIcon'  => 'fa fa-users',
        ]);
        $widgets->widget('stats')->collection('stats')->addItem($statsItem1);
    }

    /**
     * Build the table for the Users by Group stats item to be displayed within widget.
     *
     * @param string $statsId - id of the stats item
     */
    private function buildTableUsersByGroup($statsId): string
    {
        // Check if we are on Dashboard page and the chart is enabled, return empty string if not
        if (current_url() !== config('App')->baseURL . '/' . ADMIN_AREA || setting('Stats.Stats_' . $statsId) !== 'on') {
            return '';
        }
        $users = new UserModel();
        $users->select('auth_groups_users.group, COUNT(auth_groups_users.user_id) as count');
        $users->join('auth_groups_users', 'auth_groups_users.user_id = users.id');
        $users->groupBy('auth_groups_users.group');
        $users->orderBy('auth_groups_users.group');
        $users = $users->findAll();

        $table    = new Table();
        $template = [
            'table_open' => '<table style="width: 80%; background-color: transparent; color: white;">',
        ];
        $table->setTemplate($template);
        $table->setHeading('Group', 'Count');

        foreach ($users as $user) {
            $table->addRow($user->group, $user->count);
        }

        return $table->generate();
    }
}
