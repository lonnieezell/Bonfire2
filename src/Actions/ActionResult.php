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
 * What an action reports when it finishes. `{count}` in the message becomes the number of records handled.
 */
class ActionResult
{
    private function __construct(private readonly string $message, public readonly bool $failed)
    {
    }

    public static function message(string $message): static
    {
        return new static($message, false);
    }

    public static function error(string $message): static
    {
        return new static($message, true);
    }

    public function text(int $count): string
    {
        return str_replace('{count}', (string) $count, $this->message);
    }
}
