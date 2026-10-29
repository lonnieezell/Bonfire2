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
use Bonfire\Users\Models\UserModel;
use Bonfire\Users\User;
use CodeIgniter\I18n\Time;

/**
 * The "Recently active" dashboard widget: the users active within a chosen
 * window, most recent first, each linking to their account.
 */
class RecentlyActiveWidget implements DashboardWidget
{
    public const ID = 'users:recentlyActive';

    public function id(): string
    {
        return self::ID;
    }

    public function title(): string
    {
        return lang('Users.recentlyActive');
    }

    public function icon(): string
    {
        return 'fas fa-user-clock';
    }

    public function group(): string
    {
        return lang('Users.usersModTitle');
    }

    public function description(): string
    {
        return lang('Users.recentlyActiveCard');
    }

    public function sizes(): SizeRange
    {
        return new SizeRange(Size::Half, Size::Third);
    }

    public function permission(): ?string
    {
        return 'users.list';
    }

    public function fields(): array
    {
        return [
            WidgetField::choice('window', lang('Users.recentlyActiveWindow'), [
                (string) (5 * MINUTE) => lang('Users.recentlyActive5Minutes'),
                (string) HOUR         => lang('Users.recentlyActiveHour'),
                (string) DAY          => lang('Users.recentlyActive24Hours'),
            ], (string) HOUR),
            WidgetField::number('count', lang('Users.recentlyActiveCount'), default: 5, min: 1, max: 10),
        ];
    }

    /**
     * The users that are not deleted and were active in the last $seconds,
     * most recent first.
     *
     * @return list<User>
     */
    public function users(int $count, int $seconds): array
    {
        return model(UserModel::class)
            ->where('last_active >=', Time::now()->subSeconds($seconds)->toDateTimeString())
            ->orderBy('last_active', 'desc')
            ->orderBy('id', 'desc')
            ->findAll($count);
    }

    public function render(WidgetValues $values): string
    {
        return view('Bonfire\Users\Views\widgets\recently_active', [
            'title' => $this->title(),
            'users' => $this->users((int) $values->get('count'), (int) $values->get('window')),
        ]);
    }
}
