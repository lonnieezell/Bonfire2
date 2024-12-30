<?php

namespace App\Modules\Bonfire2Home\Models;

use Bonfire\Users\Models\UserModel as BaseUserModel;

class UserModel extends BaseUserModel
{
    /**
     * Returns the 5 most recently created users.
     *
     * @return array
     */
    public function getMostRecentUsers($limit = 6): array
    {
        return $this->select('users.id, first_name, last_name, secret as email, avatar')
            ->join('auth_identities', 'auth_identities.user_id = users.id')
            ->where('type', 'email_password')
            ->orderBy('users.created_at', 'DESC')
            ->findAll($limit);
    }
}
