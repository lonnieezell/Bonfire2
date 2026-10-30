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
use Bonfire\Privacy\PrunesPersonalData;
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
class UserPersonalData implements PersonalDataProvider, PrunesPersonalData
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
            'logins'      => $this->loginsOf(model(LoginModel::class), $user)->orderBy('date')->asArray()->findAll(),
            'tokenLogins' => $this->loginsOf(model(TokenLoginModel::class), $user)->orderBy('date')->asArray()->findAll(),
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

        $now = Time::now()->toDateTimeString();

        foreach ([model(LoginModel::class), model(TokenLoginModel::class)] as $logins) {
            $this->loginsOf($logins, $user)->set('identifier', $anonymous)->update();
        }

        $user->deleteResourceMeta();
        model(UserIdentityModel::class)->where('user_id', $user->id)->delete();
        model(RememberModel::class)->purgeRememberTokens($user);

        model(UserModel::class)->builder()->where('id', $user->id)->update([
            'username'   => $anonymous,
            'first_name' => null,
            'last_name'  => null,
            'avatar'     => null,
            'active'     => 0,
            'deleted_at' => $now,
            'erased_at'  => $now,
        ]);

        $avatar = $user->avatar;
        service('personalData')->afterErase(static fn () => (new AvatarStorage())->delete($avatar));
    }

    /**
     * Deletes login rows, and with them their IP addresses, once
     * they're older than the retention for each login table.
     */
    public function prune(): int
    {
        $retention = config('Privacy')->retention;
        $deleted   = 0;

        foreach (['logins' => LoginModel::class, 'tokenLogins' => TokenLoginModel::class] as $key => $model) {
            if (($retention[$key] ?? null) === null) {
                continue;
            }

            $logins = model($model);
            $logins->where('date <', Time::now()->subDays($retention[$key])->toDateTimeString())->delete();
            $deleted += db_connect()->affectedRows();
        }

        return $deleted;
    }

    /**
     * Scopes $model to logins by the user, and failed attempts with their email.
     */
    private function loginsOf(LoginModel $model, User $user): LoginModel
    {
        $model->where('user_id', $user->id);

        if ($user->email !== null) {
            $model->orWhere('identifier', $user->email);
        }

        return $model;
    }
}
