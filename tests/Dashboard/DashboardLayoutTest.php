<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Dashboard;

use Bonfire\Users\User;
use Bonfire\Widgets\Types\Stats\StatsItem;
use Config\Services;
use Tests\Support\Libraries\StubDashboard;
use Tests\Support\Libraries\TestWidget;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class DashboardLayoutTest extends TestCase
{
    protected $refresh = true;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        setting()->flush();
        Services::resetSingle('dashboard');
        $this->user = $this->createUser();
        $this->user->addGroup('superadmin');
    }

    protected function tearDown(): void
    {
        // Rendered charts leave their scripts on the shared page metadata
        Services::resetSingle('viewMeta');

        Services::resetSingle('dashboardContext');
        setting()->flush();

        parent::tearDown();
    }

    public function testAFreshInstallShowsTheCellsInOrderWithEnabledItemsExpanded(): void
    {
        setting('Stats.Stats_usersInRecycler693', 'on');
        setting('Stats.Stats_usersExpTable693', 'on');
        setting('Stats.Stats_userGroups643', 'on');
        setting('Stats.Logs_logActivity', 'on');
        setting('Stats.Charts_usersByGroupBar345', 'on');

        // Stats keep their collection's order: by title
        $this->assertSame([
            'cell:Bonfire\Dashboard\DashboardCells::quickLinks',
            'stats:userGroups643',
            'stats:usersExpTable693',
            'stats:usersInRecycler693',
            'logs:logActivity',
            'charts:usersByGroupBar345',
        ], $this->renderedIds());
    }

    public function testWidgetsSpanTheirSizeOnTheGrid(): void
    {
        setting('Stats.Stats_usersInRecycler693', 'on');

        preg_match_all('/class="bf-widget bf-span-(\d+)" data-widget-id="([^"]+)"/', $this->dashboard(), $matches);
        $spans = array_combine(array_map(html_entity_decode(...), $matches[2]), $matches[1]);

        $this->assertSame('3', $spans['stats:usersInRecycler693']);
        $this->assertSame('12', $spans['cell:Bonfire\Dashboard\DashboardCells::quickLinks']);
    }

    public function testAWidgetNotInTheLayoutIsNeverRendered(): void
    {
        $widget = new TestWidget('test:offLayout');
        service('dashboard')->register($widget);

        $this->dashboard();

        $this->assertSame(0, $widget->renders);
    }

    public function testAnItemOutsideTheLayoutDoesNotRunItsQuery(): void
    {
        StubDashboard::inject(true);
        setting('Stats.Stats_users1', 'on');
        setting('Dashboard.cells', ['Bonfire\Dashboard\DashboardCells::quickLinks']);

        $item = (new StatsItem())->setId('users1')->addValue('users');

        $this->assertNull($item->value());
    }

    public function testAnUntitledSectionHasNoHeadingOrDivider(): void
    {
        $html = $this->dashboard();

        $this->assertMatchesRegularExpression('#<section class="bf-dashboard-section">\s*<div class="bf-dashboard-grid" data-section>#', $html);
        $this->assertStringNotContainsString('bf-dashboard-section-title', $html);
    }

    private function dashboard(): string
    {
        return (string) $this->actingAs($this->user)->get(ADMIN_AREA)->response()->getBody();
    }

    /**
     * @return list<string>
     */
    private function renderedIds(): array
    {
        preg_match_all('/data-widget-id="([^"]+)"/', $this->dashboard(), $matches);

        return array_map(static fn (string $id) => html_entity_decode($id), $matches[1]);
    }
}
