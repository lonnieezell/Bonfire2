<?php

namespace App\Models;

use App\Entities\User;
use Sparks\Shield\Models\UserModel as ShieldUsers;

/**
 * This User model is ready for your customization.
 * It extends Shield's UserModel, providing many auth
 * features built right in.
 */
class UserModel extends ShieldUsers
{
    protected $returnType = User::class;

    protected $validation = [
        'email' => 'required|valid_email|is_unique[users,email,id,{id}]',
        'username' => 'required|string|is_unique[users,username,id,{id}]',
        'first_name' => 'permit_empty|string|min[3]',
        'last_name' => 'permit_empty|string|min[3]',
    ];
}
