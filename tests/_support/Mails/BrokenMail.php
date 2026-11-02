<?php

namespace Tests\Support\Mails;

use Myth\Postal\Mailable;
use Myth\Postal\Previewable;
use RuntimeException;

/**
 * A Mailable that can't be built, which must not take the preview screens down.
 */
final class BrokenMail extends Mailable implements Previewable
{
    public const ERROR = 'Broken <on> purpose';

    public static function previewInstance(): static
    {
        return new self();
    }

    protected function build(): void
    {
        throw new RuntimeException(self::ERROR);
    }
}
