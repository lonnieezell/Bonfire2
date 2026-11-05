<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Content\Controllers;

/**
 * What the admin's tree editors drag their items with.
 */
trait LoadsSortable
{
    private function addSortableScript(): void
    {
        service('viewMeta')->addScript([
            'src'            => 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js',
            'integrity'      => 'sha256-bQqDH8GbS66FF5etM5MVfoYa+3hiRZwRImNZsn4sQzc=',
            'crossorigin'    => 'anonymous',
            'referrerpolicy' => 'no-referrer',
        ]);
    }
}
