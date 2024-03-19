<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Users\Libraries;

use CodeIgniter\Shield\Models\LoginModel;

/**
 * Provides recent logins list for dashboard
 */
class LoginsCardWidget
{
    protected $viewPrefix = 'Bonfire\Users\Views\\';

    /**
     * Displays the form fields for user meta fields.
     */
    public function showRecentLogins()
    {

        /** @var LoginModel $loginModel */
        $loginModel = model(LoginModel::class);
        $logins = $loginModel->select('users.first_name, users.last_name, auth_logins.identifier, auth_logins.ip_address, auth_logins.date, auth_logins.success')
        ->orderBy('date', 'desc')
            ->join('users', 'auth_logins.user_id = users.id', 'left')
            ->findAll(6);


        return view($this->viewPrefix . '_recent_logins_card', [
            'logins' => $logins,
        ]);
    }
}
