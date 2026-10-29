<?php

namespace Tests\Users;

use Bonfire\Dashboard\WidgetValues;
use Bonfire\Users\Models\UserModel;
use Bonfire\Users\User;
use Bonfire\Users\Widgets\RecentlyActiveWidget;
use CodeIgniter\I18n\Time;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class RecentlyActiveWidgetTest extends TestCase
{
    private const FIVE_MINUTES = 5 * MINUTE;

    protected $refresh = true;
    private RecentlyActiveWidget $widget;

    protected function setUp(): void
    {
        parent::setUp();

        $this->widget = new RecentlyActiveWidget();
    }

    public function testListsOnlyUsersActiveWithinTheWindowMostRecentFirst(): void
    {
        $older  = $this->createUserActiveMinutesAgo(30);
        $recent = $this->createUserActiveMinutesAgo(1);
        $this->createUserActiveMinutesAgo(90);
        $this->createUser(); // never active

        $this->assertSame([$recent->id, $older->id], array_column($this->widget->users(5, HOUR), 'id'));
    }

    public function testLimitsToTheCountAndLeavesOutDeletedUsers(): void
    {
        $deleted = $this->createUserActiveMinutesAgo(1);
        model(UserModel::class)->delete($deleted->id);
        $second = $this->createUserActiveMinutesAgo(3);
        $first  = $this->createUserActiveMinutesAgo(2);
        $this->createUserActiveMinutesAgo(4);

        $this->assertSame([$first->id, $second->id], array_column($this->widget->users(2, HOUR), 'id'));
    }

    public function testRendersTheUsersInTheChosenWindowUpToTheCountEachLinkingToTheUser(): void
    {
        $this->actingAs($this->createUser());
        $this->createUserActiveMinutesAgo(1);
        $this->createUserActiveMinutesAgo(2);
        $this->createUserActiveMinutesAgo(30);

        $userLink = '#<a[^>]*href="' . preg_quote(site_url(ADMIN_AREA . '/users/'), '#') . '\d+"#';

        $this->assertSame(1, preg_match_all($userLink, $this->render(['window' => self::FIVE_MINUTES, 'count' => 1])));
        $this->assertSame(2, preg_match_all($userLink, $this->render(['window' => self::FIVE_MINUTES])));
    }

    public function testRendersAnEmptyStateWhenNoOneWasActiveInTheWindow(): void
    {
        $this->actingAs($this->createUser());
        $this->createUserActiveMinutesAgo(30);

        $html = $this->render(['window' => self::FIVE_MINUTES]);

        $this->assertStringContainsString('bf-rows-empty', $html);
        $this->assertStringNotContainsString('<li', $html);
    }

    public function testNeedsUsersListAndDefaultsToFiveUsersInTheLastHour(): void
    {
        $this->assertSame('users:recentlyActive', $this->widget->id());
        $this->assertSame('users.list', $this->widget->permission());

        [$window, $count] = $this->widget->fields();
        $this->assertSame((string) HOUR, $window->default);
        $this->assertSame(5, $count->default);
        $this->assertSame(10, $count->cast(50));
    }

    /**
     * @param array<string, int|string> $settings
     */
    private function render(array $settings): string
    {
        return $this->widget->render(WidgetValues::resolve($this->widget->fields(), $settings));
    }

    private function createUserActiveMinutesAgo(int $minutes): User
    {
        return $this->createUser(['last_active' => Time::now()->subMinutes($minutes)->toDateTimeString()]);
    }
}
