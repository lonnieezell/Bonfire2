<?php

declare(strict_types=1);

namespace Tests\Support\Email;

use Myth\Postal\Email;
use Myth\Postal\SendResult;
use Myth\Postal\Transport\TransportInterface;

/**
 * Stands in for a real transport in Config\Mailer's map, recording what it was
 * built with and what it was asked to send.
 */
final class RecordingTransport implements TransportInterface
{
    /**
     * The settings of each transport built.
     *
     * @var list<array<string, mixed>>
     */
    public static array $built = [];

    /**
     * The settings of the transport behind each send, and the message.
     *
     * @var list<array{settings: array<string, mixed>, email: Email}>
     */
    public static array $sent = [];

    /**
     * @param array<string, mixed> $settings
     */
    public function __construct(private readonly array $settings = [])
    {
        self::$built[] = $settings;
    }

    public static function reset(): void
    {
        self::$built = [];
        self::$sent  = [];
    }

    public function send(Email $email): SendResult
    {
        self::$sent[] = ['settings' => $this->settings, 'email' => $email];

        return SendResult::ok('recorded');
    }

    public function ping(): bool
    {
        return true;
    }
}
