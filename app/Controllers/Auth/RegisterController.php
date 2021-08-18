<?php

namespace App\Controllers\Auth;

use Sparks\Shield\Controllers\RegisterController as ShieldRegister;

class RegisterController extends ShieldRegister
{
    protected $theme = 'Auth';

    /**
     * Displays the registration form.
     */
    public function registerView()
    {
        // Check if registration is allowed
        if (! $this->config->allowRegistration)
        {
            return redirect()->back()->withInput()->with('error', lang('Auth.registerDisabled'));
        }

        return $this->render(config('Auth')->views['register']);
    }

    /**
     * Returns the URL the user should be redirected to
     * after a successful registration.
     *
     * @return string
     */
    protected function getRedirectURL()
    {
        $url = config('Auth')->redirects['register'];

        return strpos($url, 'http') === 0
            ? $url
            : rtrim(site_url($url), '/ ');
    }
}
