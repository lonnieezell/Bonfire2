<?php

namespace Bonfire\Auth\Controllers;

use Bonfire\View\Themeable;
use CodeIgniter\Shield\Controllers\LoginController as ShieldLogin;

class LoginController extends ShieldLogin
{
    use Themeable;

    public function __construct()
    {
        $this->theme = 'Auth';
        helper('auth');
    }

    /**
     * Display the login view
     */
    public function loginView(): string
    {
        // prevent login page access to logged-in users
        if (auth()->loggedIn()) {
            return redirect()->to(config('Auth')->loginRedirect());
        }

        return $this->render(config('Auth')->views['login'], [
            'allowRemember' => setting('Auth.sessionConfig')['allowRemembering'],
        ]);
    }
}
