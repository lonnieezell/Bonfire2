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

use Bonfire\Email\Models\EmailLogModel;
use CodeIgniter\I18n\Time;
use Myth\Postal\Address;
use Myth\Postal\Attachment;
use Myth\Postal\Email;
use Myth\Postal\SendResult;
use Throwable;

/**
 * Writes one email_log row for each send Postal reports, never the message body,
 * and keeps the table to the retention. Writing never throws: a missing table or
 * a database error must not turn a delivered email into a failed one.
 */
final readonly class Outbox
{
    private const DEFAULT_RETENTION_DAYS = 30;

    /**
     * @param int $pruneOneIn a write prunes one time in this many. 1 prunes every time, 0 never.
     */
    public function __construct(private int $pruneOneIn = 100)
    {
    }

    /**
     * How many days to keep rows. 0 means the Outbox is off.
     */
    public static function retentionDays(): int
    {
        helper('setting');

        $days = setting('Email.logRetentionDays');

        return $days === null ? self::DEFAULT_RETENTION_DAYS : max(0, (int) $days);
    }

    /**
     * Logs a send that Postal reported as sent or failed.
     */
    public function record(Email $email, SendResult $result): void
    {
        try {
            if (self::retentionDays() === 0) {
                return;
            }

            $mailer = config('Mailer');
            $name   = $mailer->default;
            $model  = new EmailLogModel();

            $model->insert([
                'mailer'         => $name,
                'transport'      => $mailer->mailers[$name]['transport'] ?? null,
                'mailable_class' => $email->mailableClass,
                'subject'        => $email->subject,
                'from_address'   => $email->from?->email,
                'to'             => self::emails($email->to),
                'cc'             => self::emails($email->cc),
                'bcc'            => self::emails($email->bcc),
                'attachments'    => array_map(static fn (Attachment $attachment): string => $attachment->name, $email->attachments),
                'status'         => $result->success ? 'sent' : 'failed',
                'message_id'     => $result->messageId === null ? null : mb_substr($result->messageId, 0, 255),
                'error'          => $result->error === null ? null : $this->scrub($result->error),
            ]);

            if ($this->pruneOneIn > 0 && random_int(1, $this->pruneOneIn) === 1) {
                $this->prune();
            }
        } catch (Throwable $e) {
            log_message('error', 'The Outbox could not log an email: {message}', ['message' => $e->getMessage()]);
        }
    }

    /**
     * Deletes the rows older than the retention, all of them if it is 0.
     *
     * @return int How many were deleted.
     */
    public function prune(): int
    {
        $builder = (new EmailLogModel())->builder();

        $builder->where('created_at <=', Time::now()->subDays(self::retentionDays())->toDateTimeString())->delete();

        return db_connect()->affectedRows();
    }

    /**
     * @param list<Address> $addresses
     *
     * @return list<string>
     */
    private static function emails(array $addresses): array
    {
        return array_map(static fn (Address $address): string => $address->email, $addresses);
    }

    /**
     * Errors can quote the credentials a transport was built with.
     */
    private function scrub(string $error): string
    {
        $dashboard = new MailerDashboard();

        foreach (array_keys(config('Mailer')->mailers) as $mailer) {
            $error = $dashboard->scrub((string) $mailer, $error);
        }

        return mb_substr($error, 0, 2000);
    }
}
