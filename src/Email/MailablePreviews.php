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

    /**
     * @var array<string, string> namespace => directory
     */
    private array $namespaces;

    public function __construct()
    {
        $namespaces = [];

        foreach (service('bonfire')->modules() as $namespace => $dir) {
            $namespaces[$namespace . '\Mails'] = $dir . '/Mails';
        }

        $this->namespaces = [...$namespaces, ...config('Postal')->mailableNamespaces];
        $this->previewer  = new MailablePreviewer($this->namespaces);
    }

    /**
     * Every Mailable with its subject or, if it throws while building, its error.
     *
     * @return array<string, list<array{slug: string, name: string, subject: string|null, error: string|null}>> by module
     */
    public function grouped(): array
    {
        $groups = [];

        [$found, $unloadable] = $this->scan();

        foreach ($found as $class) {
            $entry = ['slug' => self::slug($class), 'name' => self::name($class), 'subject' => null, 'error' => null];

            try {
                $entry['subject'] = $this->previewer->subject($class);
            } catch (Throwable $e) {
                $entry['error'] = $e->getMessage();
            }

            $groups[self::module($class)][] = $entry;
        }

        foreach ($unloadable as $class => $error) {
            $groups[self::module($class)][] = ['slug' => self::slug($class), 'name' => self::name($class), 'subject' => null, 'error' => $error];
        }

        ksort($groups);

        return $groups;
    }

    /**
     * The class a URL slug names, if it is one of this install's Mailables. Only
     * discovered classes qualify, so a URL can't reach one anywhere else. That
     * includes a file that can't be loaded, so its error can be shown.
     *
     * @return class-string<Mailable&Previewable>|null
     */
    public function find(string $slug): ?string
    {
        $class = str_replace('.', '\\', $slug);

        [$found, $unloadable] = $this->scan();

        return in_array($class, $found, true) || isset($unloadable[$class]) ? $class : null;
    }

    /**
     * The URL slug of each of $classes that is one of this install's Mailables. A
     * class renamed or deleted since it was logged has none.
     *
     * @param list<string> $classes
     *
     * @return array<string, string> class => slug
     */
    public function slugsFor(array $classes): array
    {
        $slugs = [];

        foreach (array_intersect($classes, $this->scan()[0]) as $class) {
            $slugs[$class] = self::slug($class);
        }

        return $slugs;
    }

    /**
     * @param class-string<Mailable&Previewable> $class
     */
    public function render(string $class): MailablePreview
    {
        try {
            return $this->previewer->render($class);
        } catch (Throwable $e) {
            // Loading the class is what threw, as find() only lets Mailables through.
            return new MailablePreview(class: $class, error: $e);
        }
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
        try {
            $email = $class::previewInstance()->render();

            // Transports would report success for a message most servers reject.
            if ($email->from === null && trim((string) setting('Email.fromEmail')) === '') {
                return SendResult::fail(lang('Email.previewNoFrom'));
            }

            // Sendmail and mail() read recipients from the headers too (Resent-* included), and a bounce goes to
            // the return path. A read receipt would go to the original address.
            $email->to         = [];
            $email->cc         = [];
            $email->bcc        = [];
            $email->returnPath = null;
            $email->rawMessage = null;
            $email->headers    = array_filter(
                $email->headers,
                static fn (string $name): bool => ! in_array(strtolower($name), ['to', 'cc', 'bcc', 'disposition-notification-to'], true)
                    && ! str_starts_with(strtolower($name), 'resent-'),
                ARRAY_FILTER_USE_KEY,
            );
            $email->to($address);

            return (new MailerDashboard())->deliver($mailer, $email);
        } catch (Throwable $e) {
            return SendResult::fail($e->getMessage());
        }
    }

    /**
     * What MailablePreviewer::discover() finds, but a file that throws while
     * loading (a parse error, a missing parent class) is reported instead of
     * taking every other Mailable down with it.
     *
     * @return array{list<class-string<Mailable&Previewable>>, array<class-string, string>} the Mailables, and the error of each class that can't be loaded
     */
    private function scan(): array
    {
        $found      = [];
        $unloadable = [];

        foreach ($this->namespaces as $namespace => $path) {
            foreach (glob(rtrim($path, '/\\') . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
                $class = rtrim($namespace, '\\') . '\\' . basename($file, '.php');

                try {
                    if ($this->previewer->isPreviewable($class)) {
                        $found[] = $class;
                    }
                } catch (Throwable $e) {
                    $unloadable[$class] = $e->getMessage();
                }
            }
        }

        sort($found);
        ksort($unloadable);

        return [$found, $unloadable];
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
