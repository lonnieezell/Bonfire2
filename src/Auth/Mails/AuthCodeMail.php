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
use Config\View;
use Myth\Postal\Mailable;
use Myth\Postal\SendResult;

/**
 * An email that gives a user a code for an auth action, and takes its From from
 * the Settings default. It renders the Markdown view named in the Auth config.
 * Installs from before those views have no such key in their published config,
 * so they keep the HTML view they may have customised.
 */
abstract class AuthCodeMail extends Mailable
{
    /**
     * A preview reads the views from config('Auth'), not the settings table.
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
     * The key in Auth::$views of the HTML view to render.
     */
    abstract protected function viewKey(): string;

    /**
     * The key in Auth::$views of the Markdown view, which wins if it is set.
     */
    abstract protected function markdownViewKey(): string;

    protected function build(): void
    {
        $views = $this->isPreview ? config('Auth')->views : setting('Auth.views');

        $this->to($this->user->email)->subject($this->subjectLine());

        if (isset($views[$this->markdownViewKey()])) {
            $this->renderMarkdown($views[$this->markdownViewKey()], $views['action_email_markdown_layout'] ?? '\Bonfire\Views\Mail\layout');

            return;
        }

        $this->html(view($views[$this->viewKey()], [
            'code'      => $this->code,
            'user'      => $this->user,
            'ipAddress' => $this->ipAddress,
            'userAgent' => $this->userAgent,
            'date'      => $this->date,
        ], ['debug' => false, 'saveData' => false]));
    }

    /**
     * Markdown views get the request details only as code spans, so they can't
     * print a raw value a visitor chose.
     */
    private function renderMarkdown(string $view, string $layout): void
    {
        $data = [
            'code'    => $this->code,
            'details' => array_filter(array_map(self::codeSpan(...), [
                lang('Auth.username') . ':' => (string) $this->user->username,
                lang('Auth.emailIpAddress') => $this->ipAddress,
                lang('Auth.emailDevice')    => $this->userAgent,
                lang('Auth.emailDate')      => $this->date,
            ])),
        ];

        // Postal's view() calls would otherwise keep the code in the shared renderer.
        $viewConfig = config(View::class);
        $saveData   = $viewConfig->saveData;

        $viewConfig->saveData = false;

        try {
            $this->layout($layout)->markdown($view, $data);
        } finally {
            $viewConfig->saveData = $saveData;
        }
    }

    /**
     * A Markdown code span, or an empty string for an empty value. esc() can't
     * protect Markdown: a user agent of [Reset](https://evil.example) would become
     * a link in the HTML of a security email, and its entities would show in the
     * text part. A client can send any bytes, and CommonMark throws on invalid UTF-8.
     */
    private static function codeSpan(string $value): string
    {
        $value = trim((string) preg_replace('/[\x00-\x1F\x7F`]+/', ' ', mb_scrub($value, 'UTF-8')));

        return $value === '' ? '' : '`' . $value . '`';
    }
}
