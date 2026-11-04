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

use CodeIgniter\Model;

/**
 * The index row of each term. `id` is the term's UUID and `data` the term file's keys as JSON.
 */
class ContentTermModel extends Model
{
    protected $table            = 'content_terms';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $allowedFields    = ['id', 'taxonomy', 'slug', 'title', 'data', 'path', 'hash', 'mtime'];

    /**
     * The row of the term with this slug.
     *
     * @return array<string, mixed>|null
     */
    public function lookup(string $taxonomy, string $slug): ?array
    {
        $row = $this->where('taxonomy', $taxonomy)->where('slug', $slug)->first();

        // A case-insensitive collation also matches other spellings of the slug.
        return $row === null || $row['slug'] !== $slug ? null : $row;
    }
}
