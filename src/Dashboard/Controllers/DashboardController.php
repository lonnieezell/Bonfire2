<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Dashboard\Controllers;

use Bonfire\Core\AdminController;
use Bonfire\Dashboard\CellManager;

/**
 * Class Dashboard
 *
 * The primary entry-point to the Bonfire admin area.
 */
class DashboardController extends AdminController
{
    /**
     * Displays the site's initial page.
     */
    public function index()
    {
        echo $this->render('Bonfire\Dashboard\Views\dashboard', [
            'cells'   => new CellManager(),
        ]);
    }
}
