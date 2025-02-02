<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Users;

use Bonfire\Search\Interfaces\SearchProviderInterface;
use Bonfire\Users\Models\UserModel;
use Bonfire\Core\Traits\SearchInMeta;

class SearchProvider extends UserModel implements SearchProviderInterface
{
    use SearchInMeta;
    /**
     * Performs a primary search for just this resource.
     */
    public function search(string $term, int $limit = 10, ?array $post = null): array
    {
        // @phpstan-ignore-next-line
        $query = $this->select('users.*')
            ->distinct();

        $query->join('auth_identities', 'auth_identities.user_id = users.id', 'inner');

        $searchInMeta = setting('Users.includeMetaFieldsInSearech');

        if (!empty($searchInMeta)) {
            // TODO: find a better way to access both of these variables -
            // Entity to which the data is assigned and table name to join meta_info with
            $query->joinMetaInfo(User::class, 'users');
        }

        $query->like('first_name', $term, 'right', true, true)
            ->orlike('last_name', $term, 'right', true, true)
            ->orLike('username', $term, 'right', true, true)
            ->orLike('secret', $term, 'both', true, true);

        if (!empty($searchInMeta)) {
            foreach ($searchInMeta as $metaField) {
                $query->orLikeInMetaInfo($metaField, $term, 'both', true, true);
            }
        }

        $query->orderBy('first_name', 'asc');

        return $query->findAll($limit);
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
