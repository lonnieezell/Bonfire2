<?php

namespace Bonfire\Email\Controllers;

use App\Controllers\BaseController;

class Settings extends BaseController
{
    /**
     * The theme to use.
     * @var string
     */
    protected $theme = 'Admin';

    public function index()
    {
        die('email settings');
    }
}
