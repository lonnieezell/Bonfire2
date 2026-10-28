<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Widgets;

use CodeIgniter\I18n\Time;
use CodeIgniter\Shield\Entities\User;
use Tests\Support\Libraries\StubDashboard;
use Tests\Support\RendersDashboard;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class DashboardWidgetsTest extends TestCase
{
    use RendersDashboard;

    private const LOG_FILE = WRITEPATH . 'logs/log-2000-01-01.log';

    protected $refresh = true;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        setting()->flush();
        $this->user = $this->createUser();
        $this->user->addGroup('superadmin');
    }

    protected function tearDown(): void
    {
        @unlink(self::LOG_FILE);
        Time::setTestNow();
        $this->forgetDashboardState();

        parent::tearDown();
    }

    public function testAWidgetOutsideTheDefaultLayoutShowsOnlyOncePlaced()
    {
        $response = $this->actingAs($this->user)->get(ADMIN_AREA);
        $placed   = $this->placedHtml((string) $response->response()->getBody());

        $response->assertOK();
        $this->assertStringNotContainsString('USER CLASSIFICATION BY GROUP', $placed);

        $this->placeOnDashboard($this->user, 'charts:usersByGroupBar345');

        $this->assertStringContainsString('USER CLASSIFICATION BY GROUP', $this->placedHtml($this->dashboardHtml($this->user)));
    }

    public function testPlacedStatsWidgetShows()
    {
        $this->placeOnDashboard($this->user, 'stats:usersInRecycler693');

        $placed = $this->placedHtml($this->dashboardHtml($this->user));

        $this->assertStringContainsString('USERS IN RECYCLER', $placed);
        $this->assertStringNotContainsString('USERS BY GROUP', $placed);
    }

    public function testStatsValueIsFilledInOnTheDashboard()
    {
        StubDashboard::inject(true);
        $this->placeOnDashboard($this->user, 'stats:usersInRecycler693');

        $response = $this->actingAs($this->user)->get(ADMIN_AREA);

        $response->assertSee('0', 'p');
    }

    public function testStatsCardShowsItsValueAndLinksToItsDetail()
    {
        StubDashboard::inject(true);
        $this->placeOnDashboard($this->user, 'stats:usersInRecycler693');
        setting('Stats.stats_showLink', true);

        $html = $this->actingAs($this->user)->get(ADMIN_AREA)->response()->getBody();

        $this->assertMatchesRegularExpression('#<div class="bf-stat">.*USERS IN RECYCLER.*<p class="bf-stat-value">0</p>#s', $html);
        $this->assertStringContainsString('<a class="bf-stat-link" href="' . site_url(ADMIN_AREA . '/tools/recycler?r=users') . '">', (string) $html);
    }

    public function testPlacedChartSitsInACard()
    {
        $this->placeOnDashboard($this->user, 'charts:usersByGroupBar345');

        $html = $this->actingAs($this->user)->get(ADMIN_AREA)->response()->getBody();

        $this->assertMatchesRegularExpression('#<section class="bf-card">\s*<header class="bf-card-head">\s*<h2>USER CLASSIFICATION BY GROUP</h2>.*?<canvas id="[^"]+" class="chart-border">#s', $html);
    }

    public function testPlacedLogActivityShowsABarPerLogFileAndALineForDaysWithout()
    {
        Time::setTestNow('2000-01-01');
        $this->placeOnDashboard($this->user, 'logs:logActivity');
        file_put_contents(self::LOG_FILE, "ERROR - 2000-01-01 10:00:00 --> One\nERROR - 2000-01-01 10:00:01 --> Two\nDEBUG - 2000-01-01 10:00:02 --> Three\n");

        $html = (string) $this->actingAs($this->user)->get(ADMIN_AREA)->response()->getBody();

        $this->assertStringContainsString('<h2>Log activity</h2>', $html);
        $this->assertStringContainsString('<strong>2</strong> errors across the last 7 days', $html);
        $this->assertStringContainsString('href="' . site_url(ADMIN_AREA . '/tools/logs') . '"', $html);
        $this->assertMatchesRegularExpression('#<a class="bf-log-stack"[^>]*href="' . preg_quote(site_url(ADMIN_AREA . '/tools/view-log/log-2000-01-01'), '#') . '"[^>]*aria-label="1/1: 2 Error, 0 Warning, 0 Info, 1 Debug"#', $html);
        $this->assertStringContainsString('<span class="bf-log-segment bf-log-error" style="flex-grow: 2"></span>', $html);
        $this->assertSame(6, substr_count($html, 'class="bf-log-none"'));
        $this->assertStringContainsString('Debug</li>', $html);
    }

    public function testLogActivityLeavesDebugOutWhenHidden()
    {
        Time::setTestNow('2000-01-01');
        $this->placeOnDashboard($this->user, 'logs:logActivity');
        setting('LogActivity.logActivity_hideDebug', 'on');
        file_put_contents(self::LOG_FILE, "DEBUG - 2000-01-01 10:00:02 --> Three\n");

        $html = (string) $this->actingAs($this->user)->get(ADMIN_AREA)->response()->getBody();

        $this->assertStringContainsString('<h2>Log activity</h2>', $html);
        $this->assertStringNotContainsString('bf-log-debug', $html);
    }

    public function testLogActivityIsHiddenWhenDisabled()
    {
        $this->placeOnDashboard($this->user, 'logs:logActivity');
        setting('Stats.Logs_logActivity', false);

        $this->actingAs($this->user)->get(ADMIN_AREA)->assertDontSee('Log activity');
    }

    public function testLogActivityIsHiddenFromUsersWhoCannotViewLogs()
    {
        $user = $this->createUser();
        $user->addPermission('admin.access');
        $this->placeOnDashboard($user, 'logs:logActivity');

        $this->actingAs($user)->get(ADMIN_AREA)->assertDontSee('Log activity');
    }

    public function testPlacedChartShowsItsCanvasAndScript()
    {
        $this->placeOnDashboard($this->user, 'charts:usersByGroupBar345');

        $response = $this->actingAs($this->user)->get(ADMIN_AREA);

        $response->assertSeeElement('canvas.chart-border');
        $response->assertSee('drawChart(');
        $response->assertSee("'bar'");
    }
}
