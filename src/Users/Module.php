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

use Bonfire\Actions\ModelSource;
use Bonfire\Core\BaseModule;
use Bonfire\Menus\MenuItem;
use Bonfire\Users\Actions\DeleteUsers;
use Bonfire\Users\Actions\EraseUsers;
use Bonfire\Users\Models\UserModel;
use Bonfire\Users\Widgets\FailedLoginsWidget;
use Bonfire\Users\Widgets\NewestUsersWidget;
use Bonfire\Users\Widgets\RecentlyActiveWidget;
use Bonfire\Users\Widgets\UsersByGroupWidget;
use Bonfire\Widgets\Types\Stats\StatsItem;

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
            'permission'      => 'users.list',
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

        service('recycler')->register('users', 'Users', UserModel::class, [
            'username', 'first_name', 'last_name', 'email',
        ]);

        service('actions')->for('users')
            ->records(new ModelSource(UserModel::class))
            ->add(new DeleteUsers())
            ->add(new EraseUsers());

        service('personalData')->register('users', new UserPersonalData());

        // Settings widgets stats on dashboard
        $widgets   = service('widgets');
        $statsItem = new StatsItem([
            'bgColor' => 'bg-danger',
            'title'   => lang('Users.usersInRecycler'),
            // assigning param directly possible, but it always executes the query, even if not on dashboard or the stats item not enabled, not optimal
            // 'value'   => (new UserModel())->onlyDeleted()->countAllResults(),
            'id'         => 'usersInRecycler693',
            'url'        => ADMIN_AREA . '/tools/recycler?r=users',
            'faIcon'     => 'fa fa-users',
            'permission' => 'users.list',
            'module'     => lang('Users.usersModTitle'),
        ]);
        // better way of retrieving deleted users, executes only when widget is displayed:
        $statsItem->addValue('users', 'deleted_at IS NOT NULL');
        // or, the following can be used with more complicated queries:
        // $statsItem->addValueByFreeQuery('SELECT COUNT(*) AS count FROM users WHERE deleted_at IS NOT NULL;');
        $widgets->widget('stats')->collection('stats')->addItem($statsItem);

        service('dashboard')
            ->register(new UsersByGroupWidget())
            ->register(new NewestUsersWidget())
            ->register(new FailedLoginsWidget())
            ->register(new RecentlyActiveWidget());
    }
}
