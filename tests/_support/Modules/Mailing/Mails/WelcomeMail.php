<?php

namespace Tests\Support\Modules\Mailing\Mails;

use Myth\Postal\Mailable;
use Myth\Postal\Previewable;

/**
 * A preview with other people's addresses, a mailer of its own and a script in
 * its HTML, none of which may get through to the admin who previews it.
 */
final class WelcomeMail extends Mailable implements Previewable
{
    public const HTML = '<h1>Welcome</h1><script>window.parent.alert("escaped")</script>';

    public static function previewInstance(): static
    {
        return new self();
    }

    protected function build(): void
    {
        $this->to('customer@example.com')
            ->replyTo('support@example.com')
            ->cc('copy@example.com')
            ->bcc('hidden@example.com')
            ->transport('first')
            ->subject('Welcome aboard')
            ->html(self::HTML)
            ->text('Welcome, in plain text');

        // Sendmail and mail() read recipients from the headers, Resent-* included.
        $this->email->header('to', 'header-to@example.com')
            ->header('Cc', 'header-cc@example.com')
            ->header('BCC', 'header-bcc@example.com')
            ->header('Resent-To', 'resent-to@example.com')
            ->header('RESENT-cc', 'resent-cc@example.com')
            ->header('resent-Bcc', 'resent-bcc@example.com')
            ->header('Disposition-Notification-To', 'receipts@example.com')
            ->header('X-Campaign', 'welcome')
            ->returnPath('bounces@example.com');
    }
}
