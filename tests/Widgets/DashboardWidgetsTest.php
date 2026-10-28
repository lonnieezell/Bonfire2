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

use CodeIgniter\Shield\Entities\User;
use Tests\Support\Libraries\StubDashboard;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class DashboardWidgetsTest extends TestCase
{
    private const LOG_FILE = WRITEPATH . 'logs/log-2000-01-01.log';

    protected $refresh = true;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser();
        $this->user->addGroup('superadmin');
    }

    protected function tearDown(): void
    {
        @unlink(self::LOG_FILE);

        parent::tearDown();
    }

    public function testNothingShowsUntilWidgetsAreEnabled()
    {
        $response = $this->actingAs($this->user)->get(ADMIN_AREA);

        $response->assertOK();
        $response->assertDontSee('USERS IN RECYCLER');
        $response->assertDontSee('USER CLASSIFICATION BY GROUP');
    }

    public function testEnabledStatsWidgetShows()
    {
        setting('Stats.Stats_usersInRecycler693', 'on');

        $response = $this->actingAs($this->user)->get(ADMIN_AREA);

        $response->assertSee('USERS IN RECYCLER');
        $response->assertDontSee('USERS BY GROUP');
    }

    public function testStatsValueIsFilledInOnTheDashboard()
    {
        StubDashboard::inject(true);
        setting('Stats.Stats_usersInRecycler693', 'on');

        $response = $this->actingAs($this->user)->get(ADMIN_AREA);

        $response->assertSee('0', 'p');
    }

    public function testStatsCardShowsItsValueAndLinksToItsDetail()
    {
        StubDashboard::inject(true);
        setting('Stats.Stats_usersInRecycler693', 'on');
        setting('Stats.stats_showLink', true);

        $html = $this->actingAs($this->user)->get(ADMIN_AREA)->response()->getBody();

        $this->assertMatchesRegularExpression('#<div class="bf-stat">.*USERS IN RECYCLER.*<p class="bf-stat-value">0</p>#s', $html);
        $this->assertStringContainsString('<a class="bf-stat-link" href="' . site_url(ADMIN_AREA . '/tools/recycler?r=users') . '">', (string) $html);
    }

    public function testEnabledChartSitsInACard()
    {
        setting('Stats.Charts_usersByGroupBar345', 'on');

        $html = $this->actingAs($this->user)->get(ADMIN_AREA)->response()->getBody();

        $this->assertMatchesRegularExpression('#<section class="bf-card">\s*<header class="bf-card-head">\s*<h2>USER CLASSIFICATION BY GROUP</h2>.*?<canvas id="[^"]+" class="chart-border">#s', $html);
    }

    public function testEnabledLogActivityShowsABarPerLogFile()
    {
        setting('Stats.Logs_logActivity', 'on');
        file_put_contents(self::LOG_FILE, "ERROR - 2000-01-01 10:00:00 --> One\nERROR - 2000-01-01 10:00:01 --> Two\nDEBUG - 2000-01-01 10:00:02 --> Three\n");

        $html = (string) $this->actingAs($this->user)->get(ADMIN_AREA)->response()->getBody();

        $this->assertStringContainsString('<h2>Log activity</h2>', $html);
        $this->assertMatchesRegularExpression('#<strong>2</strong> errors across the last \d+ log files?#', $html);
        $this->assertStringContainsString('href="' . site_url(ADMIN_AREA . '/tools/logs') . '"', $html);
        $this->assertMatchesRegularExpression('#<a class="bf-log-stack"[^>]*href="' . preg_quote(site_url(ADMIN_AREA . '/tools/view-log/log-2000-01-01'), '#') . '"[^>]*aria-label="1/1: 2 Error, 0 Warning, 0 Info, 1 Debug"#', $html);
        $this->assertStringContainsString('<span class="bf-log-segment bf-log-error" style="flex-grow: 2"></span>', $html);
        $this->assertStringContainsString('Debug</li>', $html);
    }

    public function testLogActivityLeavesDebugOutWhenHidden()
    {
        setting('Stats.Logs_logActivity', 'on');
        setting('LogActivity.logActivity_hideDebug', 'on');
        file_put_contents(self::LOG_FILE, "DEBUG - 2000-01-01 10:00:02 --> Three\n");

        $html = (string) $this->actingAs($this->user)->get(ADMIN_AREA)->response()->getBody();

        $this->assertStringContainsString('<h2>Log activity</h2>', $html);
        $this->assertStringNotContainsString('bf-log-debug', $html);
    }

    public function testLogActivityIsHiddenWhenDisabled()
    {
        setting('Stats.Logs_logActivity', false);

        $this->actingAs($this->user)->get(ADMIN_AREA)->assertDontSee('Log activity');
    }

    public function testLogActivityIsHiddenFromUsersWhoCannotViewLogs()
    {
        setting('Stats.Logs_logActivity', 'on');
        $user = $this->createUser();
        $user->addPermission('admin.access');

        $this->actingAs($user)->get(ADMIN_AREA)->assertDontSee('Log activity');
    }

    public function testEnabledChartShowsItsCanvasAndScript()
    {
        setting('Stats.Charts_usersByGroupBar345', 'on');

        $response = $this->actingAs($this->user)->get(ADMIN_AREA);

        $response->assertSeeElement('canvas.chart-border');
        $response->assertSee('drawChart(');
        $response->assertSee("'bar'");
    }
}
