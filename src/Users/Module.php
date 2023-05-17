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
use Bonfire\Widgets\Types\Cards\CardsItem;
use Bonfire\Users\Libraries\LoginsCardWidget;

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

        // Load widgets service
        $widgets   = service('widgets');

        // Card on dashboard
        $cardsItem = new CardsItem([
            'bgColor' => 'bg-gray',
            'title'   => 'Latest logins',
            'value'   => (new LoginsCardWidget)->showRecentLogins(),
            'url'     => ADMIN_AREA . '/users',
            'faIcon'  => 'fa fa-users',
            'permission' => 'users.view',
        ]);
        $widgets->widget('cards')->collection('cards')->addItem($cardsItem);

    }
}
