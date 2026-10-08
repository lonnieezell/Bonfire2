<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Recycler\Config;

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
     * Modules register the resources they make recyclable themselves, with
     * `service('recycler')->register()`. Add a resource here to register one from
     * the app, or to replace how a module's resource is displayed. Each one is
     * keyed by its alias:
     *
     *     'users' => [
     *         'label'   => 'Users',
     *         'model'   => UserModel::class,
     *         'columns' => ['username', 'email'],
     *     ],
     */
    public $resources = [];
}
