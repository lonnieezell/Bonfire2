<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Tools;

use Bonfire\Core\BaseModule;
use Bonfire\Menus\MenuItem;
use Bonfire\Tools\Actions\DeleteLogs;
use Bonfire\Tools\Libraries\LogStore;
use Bonfire\Tools\Widgets\LogActivity;
use Bonfire\Tools\Widgets\RecentErrorsWidget;

/**
 * Email Module setup
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
            'title'           => lang('Tools.systemInfoModTitle'),
            'namedRoute'      => 'sys-info',
            'fontAwesomeIcon' => 'fas fa-info-circle',
            'permission'      => 'admin.settings',
        ]);

        $itemLogs = new MenuItem([
            'title'           => lang('Tools.logsModTitle'),
            'namedRoute'      => 'sys-logs',
            'fontAwesomeIcon' => 'fas fa-clipboard-list',
            'permission'      => 'logs.view',
        ]);
        $sidebar->menu('sidebar')->collection('tools')->addItem($item);
        $sidebar->menu('sidebar')->collection('tools')->addItem($itemLogs);

        service('actions')->for('logs')
            ->records(new LogStore())
            ->add(new DeleteLogs());

        service('widgets')->widget('logs')->addItem(new LogActivity());
        service('dashboard')->register(new RecentErrorsWidget());
    }
}
