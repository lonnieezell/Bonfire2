<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Content;

use RuntimeException;

/**
 * A collection config or an entry file can't be used, or a query asked for something invalid.
 */
class ContentException extends RuntimeException
{
}
