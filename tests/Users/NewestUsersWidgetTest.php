<?php

namespace Tests\Users;

use Bonfire\Dashboard\WidgetValues;
use Bonfire\Users\Models\UserModel;
use Bonfire\Users\User;
use Bonfire\Users\Widgets\NewestUsersWidget;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class NewestUsersWidgetTest extends TestCase
{
    protected $refresh = true;
    private NewestUsersWidget $widget;

    protected function setUp(): void
    {
        parent::setUp();

        $this->widget = new NewestUsersWidget();
    }

    public function testListsTheNewestUsersFirst(): void
    {
        $oldest = $this->createUserCreatedAt('2024-01-01 10:00:00');
        $newest = $this->createUserCreatedAt('2024-01-03 10:00:00');
        $middle = $this->createUserCreatedAt('2024-01-02 10:00:00');

        $this->assertSame([$newest->id, $middle->id], array_column($this->widget->users(2), 'id'));
        $this->assertSame([$newest->id, $middle->id, $oldest->id], array_column($this->widget->users(5), 'id'));
    }

    private function createUserCreatedAt(string $date): User
    {
        $user = $this->createUser();
        $this->db->table('users')->where('id', $user->id)->update(['created_at' => $date]);

        return $user;
    }

    public function testLeavesOutDeletedUsers(): void
    {
        $deleted = $this->createUser();
        model(UserModel::class)->delete($deleted->id);

        $this->assertSame([], $this->widget->users(5));
    }

    public function testShowsAsManyUsersAsSetEachLinkingToTheUser(): void
    {
        $users = [$this->createUser(), $this->createUser(), $this->createUser()];
        $this->actingAs($users[0]);

        $html = $this->widget->render(WidgetValues::resolve($this->widget->fields(), ['count' => 2]));

        $this->assertSame(2, preg_match_all('#<a[^>]*href="' . preg_quote(site_url(ADMIN_AREA . '/users/'), '#') . '\d+"#', $html));
    }

    public function testNeedsUsersListAndShowsFiveByDefault(): void
    {
        $this->assertSame('users:newest', $this->widget->id());
        $this->assertSame('users.list', $this->widget->permission());
        $this->assertSame(5, $this->widget->fields()[0]->default);
        $this->assertSame(10, $this->widget->fields()[0]->cast(50));
    }
}
