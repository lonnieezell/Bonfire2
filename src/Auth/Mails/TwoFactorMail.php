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

use Myth\Postal\Previewable;

/**
 * Sends a user the code that finishes their two-factor login.
 */
final class TwoFactorMail extends AuthCodeMail implements Previewable
{
    protected function subjectLine(): string
    {
        return lang('Auth.email2FASubject');
    }

    protected function viewKey(): string
    {
        return 'action_email_2fa_email';
    }

    protected function markdownViewKey(): string
    {
        return 'action_email_2fa_markdown';
    }
}
