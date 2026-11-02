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
 * Sends a new user the code that activates their account.
 */
final class ActivationMail extends AuthCodeMail implements Previewable
{
    protected function subjectLine(): string
    {
        return lang('Auth.emailActivateSubject');
    }

    protected function viewKey(): string
    {
        return 'action_email_activate_email';
    }

    protected function markdownViewKey(): string
    {
        return 'action_email_activate_markdown';
    }
}
