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
use Tests\Support\TestCase;

/**
 * An item runs its query when its value is read, not when it is added.
 *
 * @internal
 */
final class LazyValueTest extends TestCase
{
    protected $refresh = true;

    public function testStatsValueIsFetchedWhenRead()
    {
        $item = (new StatsItem())->addValue('users');
        $this->createUser();

        $this->assertSame('1', $item->value());
    }

    public function testStatsFreeQueryIsFetchedWhenRead()
    {
        $item = (new StatsItem())->addValueByFreeQuery('SELECT COUNT(*) FROM ' . db_connect()->prefixTable('users'));
        $this->createUser();

        $this->assertSame('1', $item->value());
    }

    public function testAValueSetLaterTakesThePlaceOfTheQuery()
    {
        $item = (new StatsItem())->addValue('users')->setValue('5');

        $this->assertSame('5', $item->value());
    }

    public function testChartDataIsFetchedWhenRead()
    {
        $item = (new ChartsItem())->addDataset('users', 'active', 'id');
        $this->createUser();

        $this->assertSame([1], $item->data());
        $this->assertCount(1, $item->label());
    }

    public function testChartDataSetLaterKeepsTheQuerysLabels()
    {
        $this->createUser();
        $item = (new ChartsItem())->addDataset('users', 'active', 'id')->setData([3]);

        $this->assertSame([3], $item->data());
        $this->assertCount(1, $item->label());
    }
}
