<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Actions;

/**
 * What dispatching a run did: queued it as `$runId`, or ran it (or refused to)
 * and has a message for the admin.
 */
final readonly class RunOutcome
{
    private function __construct(
        public ?int $runId,
        public bool $succeeded,
        public string $message,
    ) {
    }

    public static function queued(int $runId): self
    {
        return new self($runId, true, '');
    }

    public static function ran(bool $succeeded, string $message): self
    {
        return new self(null, $succeeded, $message);
    }
}
