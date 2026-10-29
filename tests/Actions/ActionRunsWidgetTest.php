<?php

namespace Tests\Actions;

use Bonfire\Actions\Models\ActionRunModel;
use Bonfire\Actions\RunStatus;
use Bonfire\Actions\Widgets\ActionRunsWidget;
use Bonfire\Dashboard\WidgetValues;
use Bonfire\Users\User;
use Bonfire\View\Theme;
use CodeIgniter\I18n\Time;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class ActionRunsWidgetTest extends TestCase
{
    protected $refresh = true;
    private ActionRunsWidget $widget;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // The widget renders admin components, as on the dashboard
        Theme::setTheme('Admin');

        $this->widget = new ActionRunsWidget();
        $this->admin  = $this->createUser();
    }

    protected function tearDown(): void
    {
        Theme::setTheme('');

        parent::tearDown();
    }

    public function testListsTheViewersQueuedAndRunningRunsNewestFirst(): void
    {
        $queued  = $this->createRun(RunStatus::Queued, daysAgo: 2);
        $running = $this->createRun(RunStatus::Running, daysAgo: 1);
        $this->createRun(RunStatus::Running, user: $this->createUser());

        $this->assertSame([$running, $queued], $this->runIds(5, 7, false));
    }

    public function testListsFailedRunsOnlyWithinTheDays(): void
    {
        $failed = $this->createRun(RunStatus::Failed, daysAgo: 2);
        $this->createRun(RunStatus::Failed, daysAgo: 8);
        $this->createRun(RunStatus::Done, daysAgo: 1);

        $this->assertSame([$failed], $this->runIds(5, 7, false));
    }

    public function testCountsTheDaysFromWhenARunEndedNotWhenItStarted(): void
    {
        $failed = $this->createRun(RunStatus::Failed, daysAgo: 9, endedDaysAgo: 1);

        $this->assertSame([$failed], $this->runIds(5, 7, false));
    }

    public function testListsDoneRunsWithinTheDaysWhenCompletedRunsAreIncluded(): void
    {
        $queued = $this->createRun(RunStatus::Queued, daysAgo: 9);
        $done   = $this->createRun(RunStatus::Done, daysAgo: 1);
        $failed = $this->createRun(RunStatus::Failed, daysAgo: 3);
        $this->createRun(RunStatus::Done, daysAgo: 8);

        $this->assertSame([$done, $failed, $queued], $this->runIds(5, 7, true));
    }

    public function testLimitsToTheCount(): void
    {
        $this->createRun(RunStatus::Queued, daysAgo: 3);
        $second = $this->createRun(RunStatus::Queued, daysAgo: 2);
        $first  = $this->createRun(RunStatus::Failed, daysAgo: 1);

        $this->assertSame([$first, $second], $this->runIds(2, 7, false));
    }

    public function testRendersTheViewersRunsUpToTheCountEachLinkingToItsProgress(): void
    {
        $this->actingAs($this->admin);
        $queued = $this->createRun(RunStatus::Queued, daysAgo: 1);
        $done   = $this->createRun(RunStatus::Done);
        $this->createRun(RunStatus::Running, user: $this->createUser());

        $runLink = '#<a[^>]*href="' . preg_quote(site_url(ADMIN_AREA . '/actions/runs/'), '#') . '(\d+)"#';

        preg_match_all($runLink, $this->render([]), $default);
        $this->assertSame([(string) $queued], $default[1]);

        preg_match_all($runLink, $this->render(['completed' => true, 'count' => 1]), $completed);
        $this->assertSame([(string) $done], $completed[1]);
    }

    public function testRendersAnEmptyStateWhenNoRunsNeedAttention(): void
    {
        $this->actingAs($this->admin);
        $this->createRun(RunStatus::Done);

        $html = $this->render([]);

        $this->assertStringContainsString('bf-rows-empty', $html);
        $this->assertStringNotContainsString('<li', $html);
    }

    public function testNeedsNoPermissionAndDefaultsToFiveRunsOverSevenDaysWithoutCompletedOnes(): void
    {
        $this->assertSame('actions:runs', $this->widget->id());
        $this->assertNull($this->widget->permission());

        [$count, $days, $completed] = $this->widget->fields();
        $this->assertSame(5, $count->default);
        $this->assertSame(20, $count->cast(50));
        $this->assertSame(7, $days->default);
        $this->assertFalse($completed->default);
    }

    /**
     * @param array<string, bool|int|string> $settings
     */
    private function render(array $settings): string
    {
        return $this->widget->render(WidgetValues::resolve($this->widget->fields(), $settings));
    }

    /**
     * @return list<int>
     */
    private function runIds(int $count, int $days, bool $completed): array
    {
        return array_map(intval(...), array_column($this->widget->runs($this->admin, $count, $days, $completed), 'id'));
    }

    private function createRun(RunStatus $status, int $daysAgo = 0, ?User $user = null, ?int $endedDaysAgo = null): int
    {
        $runs  = model(ActionRunModel::class);
        $runId = $runs->insert([
            'list'    => 'test-users',
            'action'  => 'touch-users',
            'user_id' => ($user ?? $this->admin)->id,
            'ids'     => '[1,2,3,4]',
            'total'   => 4,
            'status'  => $status,
        ]);
        $runs->builder()->where('id', $runId)->update([
            'created_at' => Time::now()->subDays($daysAgo)->toDateTimeString(),
            'updated_at' => Time::now()->subDays($endedDaysAgo ?? $daysAgo)->toDateTimeString(),
        ]);

        return $runId;
    }
}
