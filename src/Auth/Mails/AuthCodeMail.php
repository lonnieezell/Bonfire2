<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Auth\Mails;

use Bonfire\Users\User;
use CodeIgniter\I18n\Time;
use CodeIgniter\Shield\Entities\User as ShieldUser;
use Myth\Postal\Mailable;
use Myth\Postal\SendResult;

/**
 * An email that gives a user a code for an auth action. It renders the view
 * named in the Auth config, so installs keep the templates they customised, and
 * takes its From from the Settings default.
 */
abstract class AuthCodeMail extends Mailable
{
    /**
     * A preview reads the views from config('Auth'), because setting() queries
     * the database.
     */
    private bool $isPreview = false;

    final public function __construct(
        private readonly ShieldUser $user,
        private readonly string $code,
        private readonly string $ipAddress,
        private readonly string $userAgent,
        private readonly string $date,
    ) {
        parent::__construct();
    }

    /**
     * Sample data only: an unsaved user and a fake code.
     */
    public static function previewInstance(): static
    {
        $user = new User(['username' => 'preview-user', 'email' => 'preview@example.com']);

        $mail            = new static($user, '123456', '127.0.0.1', 'Mozilla/5.0 (Preview)', Time::now()->toDateTimeString());
        $mail->isPreview = true;

        return $mail;
    }

    /**
     * Fails without a Settings From. Transports would otherwise report success
     * for a message most servers reject, and the user would never get the code.
     */
    public function send(): SendResult
    {
        if (trim((string) setting('Email.fromEmail')) === '') {
            return SendResult::fail('No From address: set one in Settings -> Email.');
        }

        return parent::send();
    }

    /**
     * The message's subject line.
     */
    abstract protected function subjectLine(): string;

    /**
     * The key in Auth::$views of the view to render.
     */
    abstract protected function viewKey(): string;

    protected function build(): void
    {
        $views = $this->isPreview ? config('Auth')->views : setting('Auth.views');

        $this->to($this->user->email)
            ->subject($this->subjectLine())
            ->html(view($views[$this->viewKey()], [
                'code'      => $this->code,
                'user'      => $this->user,
                'ipAddress' => $this->ipAddress,
                'userAgent' => $this->userAgent,
                'date'      => $this->date,
            ], ['debug' => false, 'saveData' => false]));
    }
}
