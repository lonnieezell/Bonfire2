<?php

namespace Bonfire\Users\Models;

use Bonfire\Recycler\Interfaces\CustomRecyclerQuery;
use Bonfire\Recycler\Interfaces\CustomRecyclerRestore;
use Bonfire\Users\Libraries\AvatarStorage;
use Bonfire\Users\User;
use CodeIgniter\Shield\Models\UserModel as ShieldUsers;
use Faker\Generator;

/**
 * This User model is ready for your customization.
 * It extends Shield's UserModel, providing many auth
 * features built right in.
 */
class UserModel extends ShieldUsers implements CustomRecyclerQuery, CustomRecyclerRestore
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
     * fields. Erased users are left out: they stay deleted.
     */
    public function setupRecycler(): static
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
        ")->where("{$dbPrefix}users.erased_at");
    }

    /**
     * Restores a deleted user, unless their personal data was erased.
     */
    public function recyclerRestore(int $id): bool
    {
        $this->onlyDeleted()->where('erased_at')->where('id', $id)
            ->set(['deleted_at' => null])
            ->update();

        return $this->db->affectedRows() > 0;
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
