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
    protected $refresh = true;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser();
        $this->user->addGroup('superadmin');
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

    public function testEnabledChartShowsItsCanvasAndScript()
    {
        setting('Stats.Charts_usersByGroupBar345', 'on');

        $response = $this->actingAs($this->user)->get(ADMIN_AREA);

        $response->assertSeeElement('canvas.chart-border');
        $response->assertSee('drawChart(');
        $response->assertSee("'bar'");
    }
}
