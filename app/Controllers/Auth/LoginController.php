<?php

namespace App\Controllers\Auth;

use Sparks\Shield\Controllers\LoginController as ShieldLogin;

class LoginController extends ShieldLogin
{
    protected $theme = 'Auth';

    /**
     * Display the login view
     *
     * @return string|void
     */
    public function loginView()
    {
        return $this->render(config('Auth')->views['login']);
    }

    /**
     * Returns the URL that a user should be redirected
     * to after a successful login.
     *
     * @param $user
     *
     * @return string
     */
    public function getLoginRedirect($user)
    {
        $url = config('Auth')->redirects['login'];

        return strpos($url, 'http') === 0
            ? $url
            : rtrim(site_url($url), '/ ');
    }
}
