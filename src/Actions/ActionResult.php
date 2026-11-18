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
    private function __construct(
        private readonly string $message,
        public readonly bool $failed,
        public readonly ?ResultFile $file = null,
    ) {
    }

    public static function message(string $message): static
    {
        return new static($message, false);
    }

    public static function error(string $message): static
    {
        return new static($message, true);
    }

    /**
     * A file for the admin to download as `$filename`. A run in the request sends it and deletes it; a queued run
     * keeps it for a day behind a link in its toast, for the admin who started it.
     */
    public static function file(string $path, string $filename, string $message): static
    {
        return new static($message, false, new ResultFile($path, $filename));
    }

    public function text(int $count): string
    {
        return str_replace('{count}', (string) $count, $this->message);
    }
}
