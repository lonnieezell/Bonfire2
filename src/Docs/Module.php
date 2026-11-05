<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Docs;

use Bonfire\Core\BaseModule;
use Bonfire\Menus\MenuItem;

class Module extends BaseModule
{
    /**
     * Setup our admin area needs.
     */
    public function initAdmin()
    {
        $docs = service('docs');

        if (! $docs->isAvailable()) {
            return;
        }

        $collection = service('menus')->menu('sidebar')
            ->createCollection('docs', lang('Docs.modTitle'))
            ->setFontAwesomeIcon('fas fa-book')
            ->setCollapsible();

        foreach ($docs->sections() as $section) {
            $collection->addItem(new MenuItem([
                'title'      => $section['title'],
                'url'        => rtrim(ADMIN_AREA . '/docs/' . $section['uri'], '/'),
                'permission' => 'admin.access',
            ]));
        }
    }
}
