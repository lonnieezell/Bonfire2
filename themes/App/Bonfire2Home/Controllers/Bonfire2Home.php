<?php

namespace App\Modules\Bonfire2Home\Controllers;

class Bonfire2Home extends AppController
{
    public function index(): string
    {

        // Add the page title
        $viewMeta = service('viewMeta');
        $viewMeta->setTitle('Bonfire2: Admin Area for CodeIgniter 4');

        return $this->render('App\Modules\Bonfire2Home\Views\home_view', []);
    }
}
