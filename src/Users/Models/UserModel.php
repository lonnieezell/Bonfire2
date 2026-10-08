<?php

namespace Bonfire\Users\Models;

use Bonfire\Users\Libraries\AvatarStorage;
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
    protected $beforeDelete   = ['deleteAvatar', 'deleteMeta'];

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
     * Event-triggered method to delete user avatars if the users are being purged
     * from the system
     */
    public function deleteAvatar(array $data): array
    {
        // if it is a soft delete, return at once
        if (! $data['purge']) {
            return $data;
        }

        $storage = new AvatarStorage();

        foreach ($this->purgedUsers($data) as $user) {
            /** @phpstan-ignore-next-line  TODO: any better way of accessing $avatar on user objet? It works, but phpstan complains */
            $storage->delete($user->avatar);
        }

        return $data;
    }

    /**
     * Event-triggered method to delete user meta info if the users are being purged
     * from the system
     */
    public function deleteMeta(array $data): array
    {
        // if it is a soft delete, return at once
        if (! $data['purge']) {
            return $data;
        }

        foreach ($this->purgedUsers($data) as $user) {
            if (! empty($user->allMeta())) {
                $user->deleteResourceMeta();
            }
        }

        return $data;
    }

    /**
     * @return list<User>
     */
    private function purgedUsers(array $data): array
    {
        if (empty($data['id'])) {
            return [];
        }

        return $this->withDeleted()->find($data['id']);
    }
}
