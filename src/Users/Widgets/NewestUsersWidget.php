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
use IntlDatePatternGenerator;

/**
 * The "Newest users" dashboard widget: the latest users to join, each
 * linking to their account.
 */
class NewestUsersWidget implements DashboardWidget
{
    public const ID = 'users:newest';

    public function id(): string
    {
        return self::ID;
    }

    public function title(): string
    {
        return lang('Users.newestUsers');
    }

    public function icon(): string
    {
        return 'fas fa-user-plus';
    }

    public function group(): string
    {
        return 'custom';
    }

    public function description(): string
    {
        return lang('Users.newestUsersCard');
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
            WidgetField::number('count', lang('Users.newestUsersCount'), default: 5, min: 1, max: 10),
        ];
    }

    /**
     * The users that are not deleted, newest first.
     *
     * @return list<User>
     */
    public function users(int $count): array
    {
        return model(UserModel::class)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->findAll($count);
    }

    public function render(WidgetValues $values): string
    {
        return view('Bonfire\Users\Views\widgets\newest_users', [
            'title'   => $this->title(),
            'users'   => $this->users((int) $values->get('count')),
            'pattern' => (new IntlDatePatternGenerator(service('request')->getLocale()))->getBestPattern('MMMd'),
        ]);
    }
}
