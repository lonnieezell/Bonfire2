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

use Closure;

/**
 * The fragments being rendered, outermost first, so that one which embeds itself can't go on forever.
 */
final class FragmentStack
{
    private const MAX_DEPTH = 5;

    /**
     * The entry the rendering started from, then each fragment embedded below it.
     *
     * @var list<string>
     */
    private static array $ids = [];

    /**
     * What $render gives while the fragment is on the stack, or nothing, logged, for a cycle or a depth over the limit.
     *
     * @param string            $owner    the id of the entry whose block embeds the fragment
     * @param string            $fragment the id of the fragment
     * @param Closure(): string $render
     */
    public static function render(string $owner, string $fragment, Closure $render): string
    {
        $outer = self::$ids;
        $ids   = $outer === [] ? [$owner] : $outer;

        if (in_array($fragment, $ids, true)) {
            log_message('error', 'Content: fragment {id} was not rendered, because it embeds itself.', ['id' => $fragment]);

            return '';
        }

        // The entry the rendering started from isn't embedded, so it doesn't count.
        if (count($ids) > self::MAX_DEPTH) {
            log_message('error', 'Content: fragment {id} was not rendered, because fragments are nested more than {max} deep.', ['id' => $fragment, 'max' => self::MAX_DEPTH]);

            return '';
        }

        self::$ids = [...$ids, $fragment];

        try {
            return $render();
        } finally {
            self::$ids = $outer;
        }
    }
}
