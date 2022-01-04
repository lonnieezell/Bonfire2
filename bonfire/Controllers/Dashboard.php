<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Controllers;

use App\Controllers\AdminController;

/**
 * Class Dashboard
 *
 * The primary entry-point to the Bonfire admin area.
 */
class Dashboard extends AdminController
{
    /**
     * The theme to use.
     *
     * @var string
     */
    protected $theme = 'Admin';

    /**
     * Displays the site's initial page.
     */
    public function index()
    {
        $widgets = service('widgets');
        echo $this->render('Bonfire\Views\dashboard', [
            'widgets' => $widgets,
            'manager' => $widgets->manager(),
        ]);
    }
}
