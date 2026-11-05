<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Core;

use CodeIgniter\Database\BaseConnection;

/**
 * Makes text safe to give to a model's like() or orLike().
 */
final class Like
{
    /**
     * Escapes the characters that LIKE reads as wildcards, so that they match themselves. like() only adds the
     * ESCAPE clause, so a % or _ in the term would still be a wildcard without this. Pass the result to like() with
     * $escape = true to also quote it.
     */
    public static function escape(string $term, BaseConnection $db): string
    {
        $escape = $db->likeEscapeChar;

        return str_replace([$escape, '%', '_'], [$escape . $escape, $escape . '%', $escape . '_'], $term);
    }
}
