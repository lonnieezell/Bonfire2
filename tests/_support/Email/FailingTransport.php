<?php

declare(strict_types=1);

namespace Tests\Support\Email;

use Myth\Postal\Email;
use Myth\Postal\SendResult;
use Myth\Postal\Transport\TransportInterface;
use RuntimeException;

/**
 * Fails every send with the `error` setting, by throwing if `throw` is set.
 */
final readonly class FailingTransport implements TransportInterface
{
    /**
     * @param array<string, mixed> $settings
     */
    public function __construct(private array $settings = [])
    {
    }

    public function send(Email $email): SendResult
    {
        if (isset($this->settings['throw'])) {
            throw new RuntimeException($this->settings['error']);
        }

        return SendResult::fail($this->settings['error']);
    }

    public function ping(): bool
    {
        return false;
    }
}
