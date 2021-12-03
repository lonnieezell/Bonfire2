<?php

namespace App\Controllers;

class Home extends BaseController
{
    /**
     * Displays the initial page that visitors
     * see at the root of the site.
     *
     * @return string
     */
    public function index()
    {
        return $this->render('welcome_message');
    }
}
