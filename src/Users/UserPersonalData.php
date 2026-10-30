<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Users;

use Bonfire\Privacy\PersonalDataProvider;
use Bonfire\Users\Libraries\AvatarStorage;
use Bonfire\Users\Models\UserModel;
use CodeIgniter\I18n\Time;
use CodeIgniter\Shield\Models\LoginModel;
use CodeIgniter\Shield\Models\RememberModel;
use CodeIgniter\Shield\Models\TokenLoginModel;
use CodeIgniter\Shield\Models\UserIdentityModel;

/**
 * The user's account, meta, avatar, sign-in identities and login history.
 */
class UserPersonalData implements PersonalDataProvider
{
    public function label(): string
    {
        return lang('Users.usersModTitle');
    }

    public function export(User $user): array
    {
        $account = model(UserModel::class)->asArray()->withDeleted()->find($user->id);
        unset($account['avatar']);

        // Identities hold password hashes and token hashes in their secrets
        $identities = array_map(
            static fn ($identity) => array_diff_key($identity->toArray(), ['secret' => true, 'secret2' => true]),
            model(UserIdentityModel::class)->getIdentities($user),
        );

        return [
            'account'     => $account,
            'email'       => $user->email,
            'meta'        => $user->allMetaKeyValue(),
            'avatar'      => $user->avatar,
            'identities'  => $identities,
            'logins'      => $this->logins(model(LoginModel::class), $user),
            'tokenLogins' => $this->logins(model(TokenLoginModel::class), $user),
        ];
    }

    /**
     * Anonymises the user rather than deleting the row, so content pointing
     * at it keeps working. Login rows stay for security audits, without the
     * email, until retention prunes them. See ADR 0006.
     */
    public function erase(User $user): void
    {
        $anonymous = "deleted-user-{$user->id}";

        foreach ([model(LoginModel::class), model(TokenLoginModel::class)] as $logins) {
            $logins->where('user_id', $user->id);

            if ($user->email !== null) {
                $logins->orWhere('identifier', $user->email);
            }

            $logins->set('identifier', $anonymous)->update();
        }

        $user->deleteResourceMeta();
        model(UserIdentityModel::class)->where('user_id', $user->id)->delete();
        model(RememberModel::class)->where('user_id', $user->id)->delete();

        model(UserModel::class)->builder()->where('id', $user->id)->update([
            'username'   => $anonymous,
            'first_name' => null,
            'last_name'  => null,
            'avatar'     => null,
            'active'     => 0,
            'deleted_at' => Time::now()->format('Y-m-d H:i:s'),
            'erased_at'  => Time::now()->format('Y-m-d H:i:s'),
        ]);

        $avatar = $user->avatar;
        service('personalData')->afterErase(static fn () => (new AvatarStorage())->delete($avatar));
    }

    /**
     * Logins by the user, and failed attempts with their email.
     */
    private function logins(LoginModel $model, User $user): array
    {
        $model->where('user_id', $user->id);

        if ($user->email !== null) {
            $model->orWhere('identifier', $user->email);
        }

        return array_map(static fn ($login) => $login->toArray(), $model->orderBy('date')->findAll());
    }
}
