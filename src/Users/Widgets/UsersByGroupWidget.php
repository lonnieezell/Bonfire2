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

/**
 * The "Users by group" dashboard widget: each group with a bar for its
 * number of users, scaled to the largest group.
 */
class UsersByGroupWidget implements DashboardWidget
{
    public const ID = 'users:byGroup';

    public function id(): string
    {
        return self::ID;
    }

    public function title(): string
    {
        return lang('Users.usersByGroup');
    }

    public function icon(): string
    {
        return 'fas fa-users';
    }

    public function group(): string
    {
        return 'stats';
    }

    public function description(): string
    {
        return lang('Users.usersByGroupCard');
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
            WidgetField::choice('sort', lang('Users.sortBy'), ['count' => lang('Users.sortByCount'), 'name' => lang('Users.sortByName')], 'count'),
        ];
    }

    /**
     * Every group, including those without users, with its number of users
     * that are not deleted: most first ('count') or by title ('name').
     *
     * @return list<array{title: string, count: int}>
     */
    public function rows(string $sort): array
    {
        $counts = array_column(
            model(UserModel::class)
                ->select('auth_groups_users.group')
                ->selectCount('auth_groups_users.user_id', 'count')
                ->join('auth_groups_users', 'auth_groups_users.user_id = users.id')
                ->groupBy('auth_groups_users.group')
                ->asArray()
                ->findAll(),
            'count',
            'group',
        );

        $rows = [];

        foreach (setting('AuthGroups.groups') + $counts as $group => $info) {
            $rows[] = [
                'title' => is_array($info) ? $info['title'] : $group,
                'count' => (int) ($counts[$group] ?? 0),
            ];
        }

        usort($rows, static fn (array $a, array $b) => ($sort === 'name' ? 0 : $b['count'] <=> $a['count'])
            ?: strnatcasecmp($a['title'], $b['title']));

        return $rows;
    }

    public function render(WidgetValues $values): string
    {
        $rows = $this->rows((string) $values->get('sort'));

        return view('Bonfire\Users\Views\widgets\users_by_group', [
            'title' => $this->title(),
            'rows'  => $rows,
            'most'  => max([1, ...array_column($rows, 'count')]),
        ]);
    }
}
