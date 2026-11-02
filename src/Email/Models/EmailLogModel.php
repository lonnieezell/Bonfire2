<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Email\Models;

use CodeIgniter\Model;
use DateTimeImmutable;

/**
 * The Outbox. One row per send, with the addresses and attachment names as JSON
 * lists and never a message body, because some mail carries live login tokens.
 * `status` is sent or failed.
 */
class EmailLogModel extends Model
{
    protected $table         = 'email_log';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $updatedField  = '';
    protected $allowedFields = [
        'mailer', 'transport', 'mailable_class', 'subject', 'from_address', 'to', 'cc', 'bcc',
        'attachments', 'status', 'message_id', 'error',
    ];
    protected array $casts = [
        'to'          => '?json-array',
        'cc'          => '?json-array',
        'bcc'         => '?json-array',
        'attachments' => '?json-array',
    ];

    /**
     * Narrows the Outbox to the status, mailer, recipient (To, Cc or Bcc, as text)
     * and inclusive date range in $filters. Anything else in it is ignored.
     *
     * @param array<string, mixed> $filters status, mailer, recipient, date_from, date_to
     */
    public function filter(array $filters): static
    {
        $text = static fn (string $key): string => is_string($filters[$key] ?? null) ? trim($filters[$key]) : '';

        if (in_array($text('status'), ['sent', 'failed'], true)) {
            $this->where('status', $text('status'));
        }

        if ($text('mailer') !== '') {
            $this->where('mailer', $text('mailer'));
        }

        if ($text('recipient') !== '') {
            // like() only adds the ESCAPE clause, so a % or _ typed here would still be a wildcard.
            $escape = $this->db->likeEscapeChar;
            $needle = str_replace([$escape, '%', '_'], [$escape . $escape, $escape . '%', $escape . '_'], $text('recipient'));

            $this->groupStart()
                ->like('to', $needle)
                ->orLike('cc', $needle)
                ->orLike('bcc', $needle)
                ->groupEnd();
        }

        if (self::isDate($text('date_from'))) {
            $this->where('created_at >=', $text('date_from') . ' 00:00:00');
        }

        if (self::isDate($text('date_to'))) {
            $this->where('created_at <=', $text('date_to') . ' 23:59:59');
        }

        return $this;
    }

    /**
     * The mailers that appear in the log.
     *
     * @return list<string>
     */
    public function mailers(): array
    {
        $rows = $this->builder()->select('mailer')->distinct()->where('mailer IS NOT NULL')->orderBy('mailer')->get()->getResultArray();

        return array_column($rows, 'mailer');
    }

    private static function isDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
