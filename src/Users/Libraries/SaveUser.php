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

use Bonfire\Users\Models\UserModel;
use Bonfire\Users\User;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Shield\Models\UserIdentityModel;
use ReflectionException;

/**
 * Saves the user admin form: details, status, avatar, email identity,
 * groups and meta. What the acting user is allowed to change is decided
 * by UserAccess; the caller has already validated the input.
 */
final readonly class SaveUser
{
    public function __construct(
        private UserAccess $access,
        private AvatarStorage $avatars,
        private UserModel $users,
    ) {
    }

    /**
     * @param array<string, mixed> $post
     *
     * @throws ReflectionException
     */
    public function handle(User $user, array $post, ?UploadedFile $avatar = null): User
    {
        $isNew = $user->id === null;

        $user->fill($post);

        // Mark the user active if it is created by admin, or if it is marked active by admin
        if (
            $isNew
            || (
                $user->isNotActivated()
                && $this->access->canEditUsers()
                && (int) ($post['activate'] ?? 0) === 1
            )
        ) {
            $user->active = 1;
        }

        if ($this->access->canBan($user)) {
            $ban = (int) ($post['ban'] ?? 0);

            if ($ban === 1) {
                $user->ban($post['ban_reason'] ?? null);
            } elseif ($ban === 0 && $user->isBanned()) {
                $user->unBan();
            }
        }

        $this->users->save($user);

        // We need an ID on the entity to save groups.
        if ($isNew) {
            $user->id = $this->users->getInsertID();
        }

        if ($avatar?->isValid()) {
            $filename = $this->avatars->store($avatar, $user);

            if ($filename !== null) {
                $this->users->update($user->id, ['avatar' => $filename]);
            }
        }

        $this->saveEmailIdentity($user, $post['email'] ?? null, $post['password'] ?? null);

        if ($this->access->canEditUsers()) {
            $user->syncGroups(...$this->access->allowedGroups($user, $post['groups'] ?? []));
        }

        $user->syncMeta($post['meta'] ?? []);

        return $user;
    }

    private function saveEmailIdentity(User $user, ?string $email, ?string $password): void
    {
        $identity = $user->getEmailIdentity();

        if ($identity === null) {
            helper('text');
            $user->createEmailIdentity([
                'email'    => $email,
                'password' => empty($password) ? random_string('alnum', 12) : $password,
            ]);

            return;
        }

        $identity->secret = $email;
        if ($password !== null) {
            $identity->secret2 = service('passwords')->hash($password);
        }
        if ($identity->hasChanged()) {
            model(UserIdentityModel::class)->save($identity);
        }
    }
}
