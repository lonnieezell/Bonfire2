<?php

namespace Bonfire\Users\Models;

use Bonfire\Users\User;
use CodeIgniter\Shield\Models\UserModel as ShieldUsers;
use Faker\Generator;

/**
 * This User model is ready for your customization.
 * It extends Shield's UserModel, providing many auth
 * features built right in.
 */
class UserModel extends ShieldUsers
{
    protected $returnType    = User::class;
    protected $allowedFields = [
        'username', 'status', 'status_message', 'active', 'last_active', 'deleted_at',
        'avatar', 'first_name', 'last_name',
    ];

    protected $allowCallbacks = true;
    protected $beforeDelete = ['deleteAvatar'];

    /**
     * Performs additional setup when finding objects
     * for the recycler. This might pull in additional
     * fields.
     */
    public function setupRecycler()
    {
        $dbPrefix = $this->db->getPrefix();

        return $this->select("{$dbPrefix}users.*,
            (SELECT secret
                from {$dbPrefix}auth_identities
                where user_id = {$dbPrefix}users.id
                    and type = 'email_password'
                order by last_used_at desc
                limit 1
            ) as email
        ");
    }

    public function fake(Generator &$faker): User
    {
        return new User([
            'username'   => $faker->userName(),
            'first_name' => $faker->firstName(),
            'last_name'  => $faker->lastName(),
            'active'     => true,
        ]);
    }

    /**
     * Event-triggered method to delete user avatar if the user is being purged
     * from the system
     */
    public function deleteAvatar(array $data): array
    {
        // if it is a soft delete, return at once
        if (!$data['purge']) {
            return $data;
        }

        $user = $this->withDeleted()->find($data['id'][0]); // Retrieve the entity

        if (!$user) {
            return $data;
        }

        /** @phpstan-ignore-next-line  TODO: any better way of accessing $avatar on user objet? It works, but phpstan complains */
        $userAvatar = $user->avatar;

        $avatarDir = FCPATH . (setting('Users.avatarDirectory') ?? 'uploads/avatars');
        if (
            !empty($userAvatar)
            && file_exists($avatarDir . '/' . $userAvatar)
        ) {
            @unlink($avatarDir . '/' . $userAvatar);
        }

        return $data;
    }
}
