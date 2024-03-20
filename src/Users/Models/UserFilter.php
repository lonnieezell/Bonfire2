<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Users\Models;

use Bonfire\Core\Traits\Filterable;
use CodeIgniter\I18n\Time;

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
            'title'   => 'User Role',
            'options' => 'getRoleFilters',
        ],
        'active' => [
            'title'   => 'Active?',
            'options' => [0 => 'Inactive', 1 => 'Active'],
        ],
        'banned' => [
            'title'   => 'Banned?',
            'options' => [0 => 'Not Banned', 1 => 'Banned'],
        ],
        'last_active' => [
            'title'   => 'Last Active Within',
            'type'    => 'radio',
            'options' => [
                1       => '1 day',
                2       => '2 days',
                3       => '3 days',
                7       => '1 week',
                14      => '2 weeks',
                30      => '1 month',
                90      => '3 months',
                180     => '6 months',
                365     => '1 year',
                'any'   => 'any time',
                'never' => 'never',
                'all'   => 'show all',
            ],
        ],
    ];

    /**
     * Provides filtering functionality.
     *
     * @param array $params
     *
     * @return UserFilter
     */
    public function filter(?array $params = null)
    {
       
        if (isset($params['role']) && count($params['role'])) {
            $this->distinct();
            $this->select('users.*');
            $this->join('auth_groups_users agu', 'agu.user_id = users.id')
                ->whereIn('agu.group', $params['role']);
        }

        if (isset($params['active']) && count($params['active'])) {
            $this->whereIn('users.active', $params['active']);
        }

        if (isset($params['banned']) && count($params['banned'])) {
            $this->groupStart();
            if(isset($params['banned'][0])) {
                $this->where('users.status', null);
            }
            if(isset($params['banned'][1])) {
                $this->orWhere('users.status', 'banned');
            }
            $this->groupEnd();
        }

        if (isset($params['last_active']) && is_numeric($params['last_active'])) {
            $this->where('last_active >=', Time::now()->subDays($params['last_active'])->toDateTimeString());
        } elseif (isset($params['last_active']) && $params['last_active'] === 'any') {
            $this->where('last_active !=', null);
        } elseif (isset($params['last_active']) && $params['last_active'] === 'never') {
            $this->where('last_active', null);
        }
        // omitting 'where' for $params['last_active'] == 'all'

        return $this;
    }

    /**
     * Returns a list of all roles in the system.
     */
    public function getRoleFilters(): array
    {
        helper('setting');
        $groups = setting('AuthGroups.groups');

        $use = [];

        foreach ($groups as $alias => $info) {
            $use[$alias] = $info['title'];
        }

        asort($use);

        return $use;
    }
}
