<?php

namespace Tests\Support\Mails;

use Myth\Postal\Mailable;
use Myth\Postal\Previewable;

/**
 * Stands in for a Mailable found through Config\Postal::$mailableNamespaces.
 */
final class ReceiptMail extends Mailable implements Previewable
{
    public static function previewInstance(): static
    {
        return new self();
    }

    protected function build(): void
    {
        $this->to('customer@example.com')
            ->subject('Your receipt')
            ->text('Thanks');
    }
}
