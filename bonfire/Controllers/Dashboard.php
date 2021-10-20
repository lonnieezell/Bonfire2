<?php

namespace Bonfire\Controllers;

use App\Controllers\AdminController;

/**
 * Class Dashboard
 *
 * The primary entry-point to the Bonfire admin area.
 *
 * @package App\Controllers\Bonfire
 */
class Dashboard extends AdminController
{
    /**
     * The theme to use.
     * @var string
     */
    protected $theme = 'Admin';

    /**
     * Displays the site's initial page.
     */
    public function index()
    {
        echo $this->render('Bonfire\Assets\Views\dashboard', []);
    }
}
