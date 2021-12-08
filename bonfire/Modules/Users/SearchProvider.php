<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Modules\Users;

use App\Models\UserModel;
use Bonfire\Modules\Search\Interfaces\SearchProviderInterface;

class SearchProvider extends UserModel implements SearchProviderInterface
{
    /**
     * Performs a primary search for just this resource.
     */
    public function search(string $term, int $limit = 10, ?array $post = null): array
    {
        // @phpstan-ignore-next-line
        return $this
            ->select('users.*')
            ->join('auth_identities', 'auth_identities.user_id = users.id', 'inner')
            ->like('first_name', $term, 'right', true, true)
            ->orlike('last_name', $term, 'right', true, true)
            ->orLike('username', $term, 'right', true, true)
            ->orLike('secret', $term, 'both', true, true)
            ->orderBy('first_name', 'asc')
            ->findAll($limit);
    }

    /**
     * Returns the name of the resource.
     */
    public function resourceName(): string
    {
        return 'users';
    }

    /**
     * Returns a URL to the admin area URL main list
     * for this resource.
     */
    public function resourceUrl(): string
    {
        return ADMIN_AREA . '/users';
    }

    /**
     * Returns the name of the view to use when
     * displaying the list of results for this
     * resource type.
     */
    public function resultView(): string
    {
        return 'Search/users';
    }
}
