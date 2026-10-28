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
 * Whether a run goes to the queue: never, when it has more records than the
 * action's `$queueAbove`, or always.
 */
enum Queue
{
    case Never;
    case Auto;
    case Always;
}
