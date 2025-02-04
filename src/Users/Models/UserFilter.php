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
    protected $filters = [];

    public function __construct()
    {
        parent::__construct();

        $this->filters = [
            'role' => [
                'title'   => lang('Users.userRole'),
                'options' => 'getRoleFilters',
            ],
            'active' => [
                'title'   => lang('Users.userActiveQuestion'),
                'options' => [
                    0 => lang('Users.userActiveOptionsNo'),
                    1 => lang('Users.userActiveOptionsYes'),
                ],
            ],
            'banned' => [
                'title'   => lang('Users.userBannedQuestion'),
                'options' => [
                    0 => lang('Users.userBannedOptionsNo'),
                    1 => lang('Users.userBannedOptionsYes'),
                ],
            ],
            'last_active' => [
                'title'   => lang('Users.lastActiveWintin'),
                'type'    => 'radio',
                'options' => [
                    1       => '1 ' . lang('Users.labelDay'),
                    2       => '2 ' . lang('Users.labelDays'),
                    3       => '3 ' . lang('Users.labelDays'),
                    7       => '1 ' . lang('Users.labelWeek'),
                    14      => '2 ' . lang('Users.labelWeeks'),
                    30      => '1 ' . lang('Users.labelMonth'),
                    90      => '3 ' . lang('Users.labelMonths'),
                    180     => '6 ' . lang('Users.labelMonths'),
                    365     => '1 ' . lang('Users.labelYear'),
                    'any'   => lang('Users.labelAnyTime'),
                    'never' => lang('Users.labelNever'),
                    'all'   => lang('Users.labelAll'),
                ],
            ],
        ];
    }

    /**
     * Provides filtering functionality.
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
            if (isset($params['banned'][0])) {
                $this->where('users.status', null);
            }
            if (isset($params['banned'][1])) {
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
