<?php

namespace Bonfire\Auth\Controllers;

use Bonfire\View\Themeable;
use CodeIgniter\Shield\Controllers\RegisterController as ShieldRegister;

class RegisterController extends ShieldRegister
{
    use Themeable;

    public function __construct()
    {
        $this->theme = 'Auth';
        helper('auth');
    }

    /**
     * Displays the registration form.
     */
    public function registerView()
    {
        // Check if registration is allowed
        if (! setting('Auth.allowRegistration')) {
            return redirect()->back()->withInput()->with('error', lang('Auth.registerDisabled'));
        }

        return $this->render(setting('Auth.views')['register']);
    }

    /**
     * Returns the URL the user should be redirected to
     * after a successful registration.
     */
    protected function getRedirectURL(): string
    {
        $url = setting('Auth.redirects')['register'];

        return strpos($url, 'http') === 0
            ? $url
            : rtrim(site_url($url), '/ ');
    }
}
