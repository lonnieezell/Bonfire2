<?php

namespace Bonfire\Auth\Authentication\Actions;

use Bonfire\View\Themeable;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Shield\Authentication\Actions\Email2FA as ShieldEmail2FA;
use CodeIgniter\Shield\Authentication\Authenticators\Session;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Entities\UserIdentity;
use CodeIgniter\Shield\Exceptions\RuntimeException;
use CodeIgniter\Shield\Models\UserIdentityModel;

/**
 * Class Email2FA
 *
 * Sends an email to the user with a code to verify their account.
 */
class Email2FA extends ShieldEmail2FA
{
    use Themeable;

    private string $type = Session::ID_TYPE_EMAIL_2FA;

    public function __construct()
    {
        $this->theme = 'Auth';
        helper('auth');
    }

    /**
     * Displays the "Hey we're going to send you a number to your email"
     * message to the user with a prompt to continue.
     */
    public function show(): string
    {
        /** @var Session $authenticator */
        $authenticator = auth('session')->getAuthenticator();

        $user = $authenticator->getPendingUser();
        if ($user === null) {
            throw new RuntimeException('Cannot get the pending login User.');
        }

        $this->createIdentity($user);

        return $this->render(config('Auth')->views['action_email_2fa'], [
            'user' => $user,
        ]);
    }

    /**
     * Generates the random number, saves it as a temp identity
     * with the user, and fires off an email to the user with the code,
     * then displays the form to accept the 6 digits
     *
     * @return RedirectResponse|string
     */
    public function handle(IncomingRequest $request)
    {
        $email = $request->getPost('email');

        /** @var Session $authenticator */
        $authenticator = auth('session')->getAuthenticator();

        $user = $authenticator->getPendingUser();
        if ($user === null) {
            throw new RuntimeException('Cannot get the pending login User.');
        }

        if (empty($email) || $email !== $user->email) {
            return redirect()->route('auth-action-show')->with('error', lang('Auth.invalidEmail'));
        }

        /** @var UserIdentityModel $identityModel */
        $identityModel = model(UserIdentityModel::class);

        $identity = $identityModel->getIdentityByType($user, $this->type);

        if (empty($identity)) {
            return redirect()->route('auth-action-show')->with('error', lang('Auth.need2FA'));
        }

        // Send the user an email with the code
        $email = emailer()->setFrom(setting('Email.fromEmail'), setting('Email.fromName') ?? '');
        $email->setTo($user->email);
        $email->setSubject(lang('Auth.email2FASubject'));
        $email->setMessage(view(setting('Auth.views')['action_email_2fa_email'], ['code' => $identity->secret]));

        if ($email->send(false) === false) {
            throw new RuntimeException('Cannot send email for user: ' . $user->email . "\n" . $email->printDebugger(['headers']));
        }

        // Clear the email
        $email->clear();

        return $this->render(config('Auth')->views['action_email_2fa_verify']);
    }

    /**
     * Attempts to verify the code the user entered.
     *
     * @return RedirectResponse|string
     */
    public function verify(IncomingRequest $request)
    {
        /** @var Session $authenticator */
        $authenticator = auth('session')->getAuthenticator();

        $postedToken = $request->getPost('token');

        $user = $authenticator->getPendingUser();
        if ($user === null) {
            throw new RuntimeException('Cannot get the pending login User.');
        }

        $identity = $this->getIdentity($user);

        // Token mismatch? Let them try again...
        if (! $authenticator->checkAction($identity, $postedToken)) {
            session()->setFlashdata('error', lang('Auth.invalid2FAToken'));

            return $this->render(config('Auth')->views['action_email_2fa_verify']);
        }

        // Get our login redirect url
        return redirect()->to(config('Auth')->loginRedirect());
    }

    /**
     * Returns an identity for the action of the user.
     */
    private function getIdentity(User $user): ?UserIdentity
    {
        /** @var UserIdentityModel $identityModel */
        $identityModel = model(UserIdentityModel::class);

        return $identityModel->getIdentityByType(
            $user,
            $this->type
        );
    }
}
