<?php

namespace Tests\Users;

use Bonfire\Dashboard\WidgetValues;
use Bonfire\Users\Models\UserModel;
use Bonfire\Users\Widgets\UsersByGroupWidget;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class UsersByGroupWidgetTest extends TestCase
{
    protected $refresh = true;
    private UsersByGroupWidget $widget;

    protected function setUp(): void
    {
        parent::setUp();

        // Other tests may leave stored groups behind
        setting()->forget('AuthGroups.groups');

        $this->widget = new UsersByGroupWidget();

        $this->createUser()->addGroup('user');
        $this->createUser()->addGroup('user');
        $this->createUser()->addGroup('admin');
    }

    public function testCountsTheUsersOfEveryGroupMostFirst(): void
    {
        $this->assertSame(
            ['User' => 2, 'Admin' => 1, 'Beta User' => 0, 'Developer' => 0, 'Super Admin' => 0],
            array_column($this->widget->rows('count'), 'count', 'title'),
        );
    }

    public function testSortsByGroupNameWhenAsked(): void
    {
        $this->assertSame(
            ['Admin', 'Beta User', 'Developer', 'Super Admin', 'User'],
            array_column($this->widget->rows('name'), 'title'),
        );
    }

    public function testDoesNotCountDeletedUsers(): void
    {
        $deleted = $this->createUser();
        $deleted->addGroup('admin');
        model(UserModel::class)->delete($deleted->id);

        $this->assertSame(1, array_column($this->widget->rows('count'), 'count', 'title')['Admin']);
    }

    public function testScalesEachBarToTheLargestGroup(): void
    {
        $this->actingAs($this->createUser());

        $html = $this->widget->render(WidgetValues::resolve($this->widget->fields()));

        $this->assertSame(1, substr_count($html, 'width: 100%'));
        $this->assertSame(1, substr_count($html, 'width: 50%'));
        $this->assertSame(3, substr_count($html, 'width: 0%'));
    }

    public function testNeedsUsersListAndCanBeSortedEitherWay(): void
    {
        $this->assertSame('users:byGroup', $this->widget->id());
        $this->assertSame('users.list', $this->widget->permission());
        $this->assertSame(['count', 'name'], array_keys($this->widget->fields()[0]->options));
    }
}
