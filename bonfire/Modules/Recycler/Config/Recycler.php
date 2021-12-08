<?php

namespace Bonfire\Recycler\Config;

use App\Models\UserModel;
use CodeIgniter\Config\BaseConfig;

class Recycler extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Default Resource
     * --------------------------------------------------------------------------
     *
     * The resource list that should display when the user
     * views the landing page for the recycler.
     *
     * Must be one of the resource listed in $this->resources.
     */
    public $defaultResource = 'users';

    /**
     * --------------------------------------------------------------------------
     * Available Resources
     * --------------------------------------------------------------------------
     *
     * Provides a list of the available resources that can be recycled,
     * along with some basic information about how to display that data.
     */
    public $resources = [
        'users' => [
            'label' => 'Users',
            'model' => UserModel::class,
            'columns' => [
                'username', 'first_name', 'last_name', 'email'
            ]
        ],
    ];
}
