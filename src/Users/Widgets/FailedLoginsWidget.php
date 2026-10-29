<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Users\Widgets;

use Bonfire\Dashboard\DashboardWidget;
use Bonfire\Dashboard\Size;
use Bonfire\Dashboard\SizeRange;
use Bonfire\Dashboard\WidgetField;
use Bonfire\Dashboard\WidgetValues;
use Bonfire\Users\Libraries\UserAccess;
use Bonfire\Users\Models\UserModel;
use Bonfire\Users\User;
use CodeIgniter\Shield\Authentication\Authenticators\Session;
use CodeIgniter\Shield\Entities\Login;
use CodeIgniter\Shield\Models\LoginModel;
use IntlDatePatternGenerator;

/**
 * The "Recent failed logins" dashboard widget: the latest unsuccessful
 * login attempts across all accounts.
 */
class FailedLoginsWidget implements DashboardWidget
{
    public const ID = 'users:failedLogins';

    public function id(): string
    {
        return self::ID;
    }

    public function title(): string
    {
        return lang('Users.failedLogins');
    }

    public function icon(): string
    {
        return 'fas fa-user-lock';
    }

    public function group(): string
    {
        return lang('Users.usersModTitle');
    }

    public function description(): string
    {
        return lang('Users.failedLoginsCard');
    }

    public function sizes(): SizeRange
    {
        return new SizeRange(Size::Half, Size::Third);
    }

    public function permission(): ?string
    {
        return 'users.view';
    }

    public function fields(): array
    {
        return [
            WidgetField::number('count', lang('Users.failedLoginsCount'), default: 5, min: 1, max: 20),
        ];
    }

    /**
     * The unsuccessful login attempts, newest first. Ordered by id rather
     * than date: auth_logins only grows and date isn't indexed.
     *
     * @return list<Login>
     */
    public function logins(int $count): array
    {
        return model(LoginModel::class)
            ->where('success', 0)
            ->orderBy('id', 'desc')
            ->findAll($count);
    }

    /**
     * The Security tab link of each attempt, by login id: only for email
     * attempts by a user whose Security tab the viewer may open.
     *
     * @param list<Login> $logins
     *
     * @return array<int, string>
     */
    private function securityLinks(array $logins): array
    {
        $emails = [];

        foreach ($logins as $login) {
            if ($login->id_type === Session::ID_TYPE_EMAIL_PASSWORD) {
                $emails[$login->id] = strtolower($login->identifier);
            }
        }

        if ($emails === []) {
            return [];
        }

        $users  = $this->usersByEmail(array_values($emails));
        $access = UserAccess::forCurrentUser();
        $links  = [];

        foreach ($emails as $loginId => $email) {
            $user = $users[$email] ?? null;

            if ($user !== null && $access->canManageSecurity($user->id)) {
                $links[$loginId] = $user->adminLink('security');
            }
        }

        return $links;
    }

    /**
     * The users with one of the emails, keyed by lowercase email. Shield
     * matches emails case-insensitively at login, so this does too.
     *
     * @param list<string> $emails lowercase
     *
     * @return array<string, User>
     */
    private function usersByEmail(array $emails): array
    {
        $identities = config('Auth')->tables['identities'];
        $secret     = db_connect()->protectIdentifiers("{$identities}.secret");
        $users      = model(UserModel::class)
            ->select("users.*, LOWER({$secret}) AS email_key")
            ->join($identities, "{$identities}.user_id = users.id")
            ->where("{$identities}.type", Session::ID_TYPE_EMAIL_PASSWORD)
            ->whereIn("LOWER({$secret})", $emails)
            ->findAll();

        return array_column($users, null, 'email_key');
    }

    public function render(WidgetValues $values): string
    {
        $logins = $this->logins((int) $values->get('count'));

        return view('Bonfire\Users\Views\widgets\failed_logins', [
            'title'   => $this->title(),
            'logins'  => $logins,
            'links'   => $this->securityLinks($logins),
            'pattern' => (new IntlDatePatternGenerator(service('request')->getLocale()))->getBestPattern('MMMd jm'),
        ]);
    }
}
