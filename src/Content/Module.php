<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Content;

use Bonfire\Core\BaseModule;
use Bonfire\Menus\MenuItem;

/**
 * Content Module setup
 */
class Module extends BaseModule
{
    /**
     * Adds a Content menu with an item for each collection. The sidebar shows it to those who have content.access.
     */
    public function initAdmin()
    {
        // Reading every collection's config is for those who'll see the menu.
        if (! auth()->user()?->can('content.access')) {
            return;
        }

        $collections = Collection::all(config('Content')->contentPath);

        if ($collections === []) {
            return;
        }

        $menu = service('menus')->menu('sidebar')
            ->createCollection('entries', lang('Content.modTitle'))
            ->setFontAwesomeIcon('fas fa-newspaper')
            ->setCollapsible();

        foreach ($collections as $collection) {
            $menu->addItem(new MenuItem([
                'title'      => esc($collection->title),
                'url'        => route_to('content-list', $collection->handle),
                'permission' => 'content.access',
            ]));
        }
    }
}
