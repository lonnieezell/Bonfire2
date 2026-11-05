<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Content\Models;

use CodeIgniter\I18n\Time;
use CodeIgniter\Model;

/**
 * The index row of each entry. `id` is the entry's UUID and `data` its front matter as JSON.
 */
class ContentEntryModel extends Model
{
    protected $table            = 'content_entries';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $allowedFields    = [
        'id', 'collection', 'blueprint', 'slug', 'uri', 'parent_id', 'sort', 'status', 'published_at',
        'title', 'data', 'body', 'path', 'hash', 'mtime',
    ];

    /**
     * Keeps entries that are published and whose published_at, if any, has passed.
     */
    public function published(): self
    {
        $this->where('status', 'published')
            ->groupStart()
            ->where('published_at')
            ->orWhere('published_at <=', Time::now()->toDateTimeString())
            ->groupEnd();

        return $this;
    }
}
