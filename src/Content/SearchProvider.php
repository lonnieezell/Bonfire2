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

use Bonfire\Content\Models\ContentEntryModel;
use Bonfire\Core\Like;
use Bonfire\Search\Interfaces\SearchProviderInterface;

/**
 * Puts entries in the admin's global search. The index row holds the raw Markdown body, so a LIKE on it is enough.
 */
class SearchProvider extends ContentEntryModel implements SearchProviderInterface
{
    /**
     * The rows of entries whose title or Markdown body contain the term. Trashed entries are left out by the model,
     * and so is everything for someone without content.access.
     */
    public function search(string $term, int $limit = 10, ?array $post = null): array
    {
        if (! $this->canAccess()) {
            return [];
        }

        // In development, this picks up files changed since the last request.
        content();

        $needle = Like::escape($term, $this->db);

        return $this->select('id, collection, title, status')
            ->groupStart()
            ->like('title', $needle, 'both', true, true)
            ->orLike('body', $needle, 'both', true, true)
            ->groupEnd()
            ->orderBy('title', 'asc')
            ->findAll($limit);
    }

    /**
     * Returns the name of the resource.
     */
    public function resourceName(): string
    {
        return lang('Content.modTitle');
    }

    /**
     * Where "see all" goes. Content has a list for each collection and none for all of it, so this is the first
     * collection's list, or the dashboard when there are no collections or the user can't open them.
     */
    public function resourceUrl(): string
    {
        if (! $this->canAccess()) {
            return ADMIN_AREA;
        }

        $first = Collection::all(config('Content')->contentPath)[0] ?? null;

        return $first === null ? ADMIN_AREA : route_to('content-list', $first->handle);
    }

    /**
     * In the module, not the Admin theme, so it reaches installs that published their own copy of the theme.
     */
    public function resultView(): string
    {
        return 'Bonfire\Content\Views\search_results';
    }

    private function canAccess(): bool
    {
        return auth()->user()->can('content.access');
    }
}
