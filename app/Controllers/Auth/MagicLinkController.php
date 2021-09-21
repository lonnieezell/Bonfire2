<?php

namespace App\Controllers\Auth;

use Bonfire\View\Themeable;
use Sparks\Shield\Controllers\MagicLinkController as ShieldMagicLinkController;

class MagicLinkController extends ShieldMagicLinkController
{
    use Themeable;

    public function __construct()
    {
        parent::__construct();

        $this->theme = 'Auth';
    }

    /**
     * Displays the view to enter their email address
     * so an email can be sent to them.
     */
    public function loginView()
    {
        echo $this->render(setting('Auth.views')['magic-link-login']);
    }

    /**
     * Display the "What's happening/next" message to the user.
     *
     * @return string
     */
    protected function displayMessage()
    {
        return $this->render(setting('Auth.views')['magic-link-message']);
    }
}
