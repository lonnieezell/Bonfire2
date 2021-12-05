<?php

namespace Bonfire\Modules\Users;

use App\Models\UserModel;
use Bonfire\Config\BaseModule;
use Bonfire\Libraries\Menus\MenuItem;
use Bonfire\Libraries\Widgets\Stats\StatsItem;
use Bonfire\Resources\ResourceTab;

/**
 * User Module setup
 *
 * @package Bonfire\User
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
        $item = new MenuItem([
            'title' => 'Users',
            'namedRoute' => 'user-settings',
            'fontAwesomeIcon' => 'fas fa-user',
        ]);
        $sidebar->menu('sidebar')->collection('settings')->addItem($item);

        // Content Menu for sidebar
        $item = new MenuItem([
            'title' => 'Users',
            'namedRoute' => 'user-list',
            'fontAwesomeIcon' => 'fas fa-users'
        ]);
        $sidebar->menu('sidebar')->collection('content')->addItem($item);

		// Settings widgets stats on dashboard
		$widgets = service('widgets');
		$users = new UserModel();
		$statsItem = new StatsItem([
			'bgColor' => 'bg-blue',
			'title' => 'Users',
			'value' => $users->countAll(),
			'url' => ADMIN_AREA . '/users',
			'faIcon' => 'fa fa-user',
		]);
		$widgets->widget("stats")->collection('users')->addItem($statsItem);
    }
}
