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

use Bonfire\Widgets\Types\Charts\ChartsItem;
use Bonfire\Widgets\Types\Stats\StatsItem;
use Tests\Support\Libraries\StubDashboard;
use Tests\Support\RendersDashboard;
use Tests\Support\TestCase;

/**
 * An item only runs its query on the dashboard, when it is placed on the
 * viewer's dashboard.
 *
 * @internal
 */
final class LazyValueTest extends TestCase
{
    use RendersDashboard;

    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();

        setting()->flush();
        $user = $this->createUser();
        $this->actingAs($user);
        $this->placeOnDashboard($user, 'stats:users1', 'charts:chart1', 'charts:chart2');
    }

    protected function tearDown(): void
    {
        $this->forgetDashboardState();

        parent::tearDown();
    }

    public function testStatsValueIsFetchedOnTheDashboardWhenPlaced()
    {
        StubDashboard::inject(true);
        $item = (new StatsItem())->setId('users1');

        $this->assertSame('1', $item->addValue('users')->value());
    }

    public function testStatsFreeQueryIsFetchedOnTheDashboardWhenPlaced()
    {
        StubDashboard::inject(true);
        $item = (new StatsItem())->setId('users1');

        $this->assertSame('1', $item->addValueByFreeQuery($this->countUsers())->value());
    }

    public function testStatsValueIsSkippedWhenNotPlaced()
    {
        StubDashboard::inject(true);
        $item = (new StatsItem())->setId('users2');

        $this->assertNull($item->addValue('users')->value());
        $this->assertNull($item->addValueByFreeQuery($this->countUsers())->value());
    }

    public function testStatsValueIsSkippedOffTheDashboard()
    {
        StubDashboard::inject(false);
        $item = (new StatsItem())->setId('users1');

        $this->assertNull($item->addValue('users')->value());
        $this->assertNull($item->addValueByFreeQuery($this->countUsers())->value());
    }

    public function testChartDataIsFetchedOnTheDashboardWhenPlaced()
    {
        StubDashboard::inject(true);
        $item = (new ChartsItem())->setId('chart1');

        $item->addDataset('users', 'active', 'id');

        $this->assertSame([1], $item->data());
    }

    public function testChartDataIsSkippedWhenNotPlacedOrOffTheDashboard()
    {
        StubDashboard::inject(true);
        $unplaced = (new ChartsItem())->setId('chart3');
        $unplaced->addDataset('users', 'active', 'id');

        StubDashboard::inject(false);
        $elsewhere = (new ChartsItem())->setId('chart2');
        $elsewhere->addDataset('users', 'active', 'id');

        $this->assertNull($unplaced->data());
        $this->assertNull($elsewhere->data());
    }

    private function countUsers(): string
    {
        return 'SELECT COUNT(*) FROM ' . db_connect()->prefixTable('users');
    }
}
