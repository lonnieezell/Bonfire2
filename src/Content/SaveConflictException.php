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

/**
 * The entry's file is not the one the form was opened on, so saving would overwrite someone else's changes.
 */
final class SaveConflictException extends ContentException
{
}
