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
use Tests\Support\TestCase;

/**
 * An item only runs its query on the dashboard, and only when enabled.
 *
 * @internal
 */
final class LazyValueTest extends TestCase
{
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();

        setting()->flush();
        $this->createUser();
    }

    public function testStatsValueIsFetchedOnTheDashboardWhenEnabled()
    {
        StubDashboard::inject(true);
        $item = (new StatsItem())->setId('users1');
        $item->settings()->store(true);

        $this->assertSame('1', $item->addValue('users')->value());
    }

    public function testStatsFreeQueryIsFetchedOnTheDashboardWhenEnabled()
    {
        StubDashboard::inject(true);
        $item = (new StatsItem())->setId('users1');
        $item->settings()->store(true);

        $this->assertSame('1', $item->addValueByFreeQuery($this->countUsers())->value());
    }

    public function testStatsValueIsSkippedWhenDisabled()
    {
        StubDashboard::inject(true);
        $item = (new StatsItem())->setId('users1');

        $this->assertNull($item->addValue('users')->value());
        $this->assertNull($item->addValueByFreeQuery($this->countUsers())->value());
    }

    public function testStatsValueIsSkippedOffTheDashboard()
    {
        StubDashboard::inject(false);
        $item = (new StatsItem())->setId('users1');
        $item->settings()->store(true);

        $this->assertNull($item->addValue('users')->value());
        $this->assertNull($item->addValueByFreeQuery($this->countUsers())->value());
    }

    public function testChartDataIsFetchedOnTheDashboardWhenEnabled()
    {
        StubDashboard::inject(true);
        $item = (new ChartsItem())->setId('chart1');
        $item->settings()->store(true);

        $item->addDataset('users', 'active', 'id');

        $this->assertSame([1], $item->data());
    }

    public function testChartDataIsSkippedWhenDisabledOrOffTheDashboard()
    {
        StubDashboard::inject(true);
        $disabled = (new ChartsItem())->setId('chart1')->addDataset('users', 'active', 'id');

        StubDashboard::inject(false);
        $elsewhere = (new ChartsItem())->setId('chart2');
        $elsewhere->settings()->store(true);
        $elsewhere->addDataset('users', 'active', 'id');

        $this->assertNull($disabled->data());
        $this->assertNull($elsewhere->data());
    }

    private function countUsers(): string
    {
        return 'SELECT COUNT(*) FROM ' . db_connect()->prefixTable('users');
    }
}
