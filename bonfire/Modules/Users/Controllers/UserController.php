<?php

namespace Bonfire\Modules\Users\Controllers;

use App\Controllers\AdminController;

class UserController extends AdminController
{
    protected $theme = 'Admin';

    protected $viewPrefix = 'Bonfire\Modules\Users\Views\\';

    /**
     * Display the uses currently in the system.
     *
     * @return string
     */
	public function list()
	{
	    $userModel = model('UserModel');

		return $this->render($this->viewPrefix .'list', [
		    'headers' => [
		        'email' => 'Email',
                'name' => 'Name',
                'groups' => 'Groups',
                'last_login' => 'Last Login'
            ],
		    'showSelectAll' => true,
		    'users' => $userModel->paginate(setting('App.perPage')),
            'pager' => $userModel->pager,
        ]);
	}

    /**
     * Display the "new user" form.
     */
    public function create()
    {
        return $this->render($this->viewPrefix .'form');
	}
}
