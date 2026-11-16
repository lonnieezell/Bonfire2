<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Media;

use Bonfire\Core\BaseModule;
use Bonfire\Menus\MenuItem;

/**
 * Media Module setup
 */
class Module extends BaseModule
{
    /**
     * Adds Media to the Content section of the sidebar, for those who have media.access.
     */
    public function initAdmin()
    {
        service('menus')->menu('sidebar')->collection('content')->addItem(new MenuItem([
            'title'           => lang('Media.modTitle'),
            'namedRoute'      => 'media-list',
            'fontAwesomeIcon' => 'fas fa-photo-video',
            'permission'      => 'media.access',
        ]));
    }
}
