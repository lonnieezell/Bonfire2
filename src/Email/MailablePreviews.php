<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Email;

use Myth\Postal\Mailable;
use Myth\Postal\Preview\MailablePreview;
use Myth\Postal\Preview\MailablePreviewer;
use Myth\Postal\Previewable;
use Myth\Postal\SendResult;
use Throwable;

/**
 * The previewable Mailables of this install: those in the Mails folder of every
 * Bonfire module, plus Config\Postal::$mailableNamespaces.
 */
final readonly class MailablePreviews
{
    private MailablePreviewer $previewer;

    public function __construct()
    {
        $namespaces = [];

        foreach (service('bonfire')->modules() as $namespace => $dir) {
            $namespaces[$namespace . '\Mails'] = $dir . '/Mails';
        }

        $this->previewer = new MailablePreviewer([...$namespaces, ...config('Postal')->mailableNamespaces]);
    }

    /**
     * Every Mailable with its subject or, if it throws while building, its error.
     *
     * @return array<string, list<array{class: class-string, slug: string, name: string, subject: string|null, error: string|null}>> by module
     */
    public function grouped(): array
    {
        $groups = [];

        foreach ($this->previewer->discover() as $class) {
            $entry = ['class' => $class, 'slug' => self::slug($class), 'name' => self::name($class), 'subject' => null, 'error' => null];

            try {
                $entry['subject'] = $this->previewer->subject($class);
            } catch (Throwable $e) {
                $entry['error'] = $e->getMessage();
            }

            $groups[self::module($class)][] = $entry;
        }

        ksort($groups);

        return $groups;
    }

    /**
     * The class a URL slug names, if it is one of this install's Mailables. Only
     * discovered classes qualify, so a URL can't reach one anywhere else.
     *
     * @return class-string<Mailable&Previewable>|null
     */
    public function find(string $slug): ?string
    {
        $class = str_replace('.', '\\', $slug);

        return in_array($class, $this->previewer->discover(), true) ? $class : null;
    }

    /**
     * @param class-string<Mailable&Previewable> $class
     */
    public function render(string $class): MailablePreview
    {
        return $this->previewer->render($class);
    }

    /**
     * Sends the Mailable's preview to $address alone, through the named mailer.
     * Goes straight to the mailer because Mailable::send() would use the
     * Mailable's own mailer. Failures come back in the result, never as exceptions.
     *
     * @param class-string<Mailable&Previewable> $class
     */
    public function send(string $class, string $address, string $mailer): SendResult
    {
        if (! array_key_exists($mailer, config('Mailer')->mailers)) {
            return SendResult::fail(lang('Email.previewUnknownMailer'));
        }

        try {
            $email = $class::previewInstance()->render();

            // Transports would report success for a message most servers reject.
            if ($email->from === null && trim((string) setting('Email.fromEmail')) === '') {
                return SendResult::fail(lang('Email.previewNoFrom'));
            }

            $email->to  = [];
            $email->cc  = [];
            $email->bcc = [];
            $email->to($address);

            return service('mailer')->mailer($mailer)->send($email);
        } catch (Throwable $e) {
            return SendResult::fail($e->getMessage());
        }
    }

    /**
     * The class as a URL segment. CodeIgniter's default permittedURIChars rejects
     * a backslash, so dots stand in for it.
     */
    private static function slug(string $class): string
    {
        return str_replace('\\', '.', $class);
    }

    /**
     * The module part of a namespace: Auth for Bonfire\Auth\Mails, App for App\Mails.
     */
    private static function module(string $class): string
    {
        $parts = array_slice(explode('\\', $class), 0, -1);

        if (count($parts) > 1 && end($parts) === 'Mails') {
            array_pop($parts);
        }

        return (string) end($parts);
    }

    /**
     * A readable name: Two Factor for TwoFactorMail.
     */
    private static function name(string $class): string
    {
        $short = substr((string) strrchr('\\' . $class, '\\'), 1);
        $short = preg_replace('/Mail$/', '', $short) ?: $short;

        return trim((string) preg_replace('/(?<!^)[A-Z]/', ' $0', $short));
    }
}
