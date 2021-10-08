<?php

namespace App\Models;

use App\Entities\User;
use Faker\Generator;
use Sparks\Shield\Models\UserModel as ShieldUsers;

/**
 * This User model is ready for your customization.
 * It extends Shield's UserModel, providing many auth
 * features built right in.
 */
class UserModel extends ShieldUsers
{
    protected $returnType = User::class;

    protected $allowedFields = [
        'username', 'status', 'status_message', 'active', 'last_active', 'deleted_at',
        'avatar', 'first_name', 'last_name',
    ];

    public function fake(Generator &$faker)
    {
        return [
            'username' => $faker->userName,
            'first_name' => $faker->firstName,
            'last_name' => $faker->lastName,
            'active'   => true,
        ];
    }
}
