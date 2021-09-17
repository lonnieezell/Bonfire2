<?php

namespace Bonfire\Modules\Users\Models;

use App\Models\UserModel;
use Bonfire\Traits\Filterable;

class UserFilter extends UserModel
{
    use Filterable;

    /**
     * The filters that can be applied to
     * lists of Users
     *
     * @var array
     */
    protected $filters = [
        'role' => [
            'title' => 'User Role',
            'options' => 'getRoleFilters'
        ],
        'active' => [
            'title' => 'Active?',
            'options' => [0 => 'Inactive', 1 => 'Active']
        ],
        'status' => [
            'title' => 'Status',
            'options' => 'getStatusFilters'
        ],
        'last_active' => [
            'title' => 'Last Active',
            'options' => [
                1 => '1 day',
                2 => '2 days',
                3 => '3 days',
                7 => '1 week',
                14 => '2 weeks',
                30 => '1 month',
                90 => '3 months',
                180 => '6 months',
                365 => '1 year',
                366 => '> 1 year'
            ]
        ]
    ];

    /**
     * Performs a search on the following fields:
     *  - username
     *  - email
     *  - first name
     *  - last name
     *
     * @todo Implement user search
     *
     * @param string|null $term
     */
    public function search(string $term=null): UserFilter
    {
        return $this;
    }

    /**
     * Provides filtering functionality.
     *
     * @param array $params
     *
     * @return UserFilter
     */
    public function filter(array $params=null)
    {
        $this->select('users.*');

        if(isset($params['role']) && count($params['role'])) {
            $this->join('auth_groups_users agu', 'agu.user_id = users.id')
                ->whereIn('agu.group', $params['role']);
        }

        if(isset($params['active']) && count($params['active'])) {
            $this->whereIn('users.active', $params['active']);
        }

        return $this;
    }

    /**
     * Returns the available status' to filter by.
     * This is determined by grabbing unique values
     * from the database.
     *
     * @return array
     */
    public function getStatusFilters(): array
    {
        // @todo work with real statuses
        return ['banned' => 'Banned'];
    }

    /**
     * Returns a list of all roles in the system.
     *
     * @return array
     */
    public function getRoleFilters(): array
    {
        $groups = setting('AuthGroups.groups');

        $use = [];
        foreach($groups as $alias => $info) {
            $use[$alias] = $info['title'];
        }

        asort($use);

        return $use;
    }
}
