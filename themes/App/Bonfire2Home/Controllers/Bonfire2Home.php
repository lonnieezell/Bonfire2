<?php

namespace App\Modules\Bonfire2Home\Controllers;

class Bonfire2Home extends AppController
{
    public function index(): string
    {
        //return view('bonfire2/home_view');
        return $this->render('App\Modules\Bonfire2Home\Views\home_view', []);
    }
}
