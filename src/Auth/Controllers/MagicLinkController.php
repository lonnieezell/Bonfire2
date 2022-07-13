<?php

namespace Bonfire\Auth\Controllers;

use Bonfire\View\Themeable;
use CodeIgniter\Shield\Controllers\MagicLinkController as ShieldMagicLinkController;

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
    public function loginView(): string
    {
        return $this->render(setting('Auth.views')['magic-link-login']);
    }

    /**
     * Display the "What's happening/next" message to the user.
     */
    protected function displayMessage(): string
    {
        return $this->render(setting('Auth.views')['magic-link-message']);
    }
}
