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
use Tests\Support\Libraries\TestWidget;
use Tests\Support\RendersDashboard;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class DashboardLayoutTest extends TestCase
{
    use RendersDashboard;

    protected $refresh = true;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->forgetDashboardState();
        $this->user = $this->createUser();
        $this->user->addGroup('superadmin');
    }

    protected function tearDown(): void
    {
        $this->forgetDashboardState();

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
        ], $this->placedIds($this->user));
    }

    public function testWidgetsSpanTheirSizeOnTheGrid(): void
    {
        setting('Stats.Stats_usersInRecycler693', 'on');

        preg_match_all('/class="bf-widget bf-span-(\d+)" data-widget-id="([^"]+)"/', $this->placedHtml($this->dashboardHtml($this->user)), $matches);
        $spans = array_combine(array_map(html_entity_decode(...), $matches[2]), $matches[1]);

        $this->assertSame('3', $spans['stats:usersInRecycler693']);
        $this->assertSame('12', $spans['cell:Bonfire\Dashboard\DashboardCells::quickLinks']);
    }

    public function testAWidgetNotInTheLayoutIsNeverRendered(): void
    {
        $widget = new TestWidget('test:offLayout');
        service('dashboard')->register($widget);

        $this->dashboardHtml($this->user);

        $this->assertSame(0, $widget->renders);
    }

    public function testAnUntitledSectionHasNoHeadingOrDivider(): void
    {
        $html = $this->dashboardHtml($this->user);

        $this->assertSame(1, substr_count($this->placedHtml($html), '<section class="bf-dashboard-section"'));
        $this->assertStringNotContainsString('bf-dashboard-section-title', $html);
    }
}
