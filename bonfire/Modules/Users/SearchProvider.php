<?php

namespace Bonfire\Modules\Users;

use Bonfire\Modules\Search\Interfaces\SearchProviderInterface;
use App\Models\UserModel;

class SearchProvider extends UserModel implements SearchProviderInterface
{
    /**
     * Performs a primary search for just this resource.
     *
     * @param string     $term
     * @param int        $limit
     * @param array|null $post
     *
     * @return array
     */
    public function search(string $term, int $limit=10, array $post=null): array
    {
        /* @phpstan-ignore-next-line */
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
     *
     * @return string
     */
    public function resourceName(): string
    {
        return 'users';
    }

    /**
     * Returns a URL to the admin area URL main list
     * for this resource.
     *
     * @return string
     */
    public function resourceUrl(): string
    {
        return ADMIN_AREA .'/users';
    }

    /**
     * Returns the name of the view to use when
     * displaying the list of results for this
     * resource type.
     *
     * @return string
     */
    public function resultView(): string
    {
        return 'Search/users';
    }
}
