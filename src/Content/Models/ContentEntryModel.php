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

use Bonfire\Content\ContentException;
use Bonfire\Content\EntryStore;
use Bonfire\Core\Like;
use Bonfire\Recycler\Interfaces\CustomRecyclerPurge;
use Bonfire\Recycler\Interfaces\CustomRecyclerQuery;
use Bonfire\Recycler\Interfaces\CustomRecyclerRestore;
use Closure;
use CodeIgniter\I18n\Time;
use CodeIgniter\Model;

/**
 * The index row of each entry. `id` is the entry's UUID and `data` its front matter as JSON.
 */
class ContentEntryModel extends Model implements CustomRecyclerQuery, CustomRecyclerRestore, CustomRecyclerPurge
{
    protected $table            = 'content_entries';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $allowedFields    = [
        'id', 'collection', 'blueprint', 'slug', 'uri', 'parent_id', 'sort', 'status', 'published_at',
        'title', 'data', 'body', 'search_text', 'path', 'hash', 'mtime', 'deleted_at',
    ];

    /**
     * Why the Recycler's last restore or purge was refused.
     */
    private ?string $refusal = null;

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

    /**
     * Keeps entries whose title or search text (the body and blocks as plain text) contain the term, ignoring spaces
     * around it. A % or _ in it matches itself, and a blank term matches nothing.
     */
    public function matching(string $term): self
    {
        $term = trim($term);

        if ($term === '') {
            return $this->where('1 = 0', null, false);
        }

        $needle = Like::escape($term, $this->db);

        return $this->groupStart()
            ->like('title', $needle, 'both', true, true)
            ->orLike('search_text', $needle, 'both', true, true)
            ->groupEnd();
    }

    /**
     * The Recycler lists every column it's asked to show, but never the contents of an entry.
     */
    public function setupRecycler(): static
    {
        return $this->select('id, collection, blueprint, slug, uri, parent_id, sort, status, published_at, title, path, hash, mtime, deleted_at');
    }

    public function recyclerRestore(int|string $id): bool
    {
        return $this->recycle(static fn (EntryStore $store): bool => $store->restore((string) $id));
    }

    public function recyclerPurge(int|string $id): bool
    {
        return $this->recycle(static fn (EntryStore $store): bool => $store->purge((string) $id));
    }

    /**
     * The Recycler shows these when a restore or purge is refused.
     */
    public function errors(bool $forceDB = false)
    {
        return $this->refusal === null ? parent::errors($forceDB) : [$this->refusal];
    }

    /**
     * @param Closure(EntryStore): bool $action
     */
    private function recycle(Closure $action): bool
    {
        $this->refusal = null;

        try {
            return $action(new EntryStore(config('Content')->contentPath));
        } catch (ContentException $e) {
            $this->refusal = $e->getMessage();

            return false;
        }
    }
}
