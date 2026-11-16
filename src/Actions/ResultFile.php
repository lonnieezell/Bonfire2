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
 * A file an action wrote for the admin, at `$path`, to download as `$name`.
 */
final readonly class ResultFile
{
    public function __construct(public string $path, public string $name)
    {
    }
}
