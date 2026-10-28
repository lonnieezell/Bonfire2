<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Dashboard;

use Bonfire\Core\BaseModule;
use Bonfire\Dashboard\Widgets\QuickLinksWidget;

class Module extends BaseModule
{
    public function initAdmin()
    {
        service('dashboard')->register(new QuickLinksWidget());
    }
}
