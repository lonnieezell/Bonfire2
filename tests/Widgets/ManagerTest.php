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

use Bonfire\Widgets\Manager;
use Bonfire\Widgets\Types\Charts\Charts;
use Bonfire\Widgets\Types\Charts\ChartsItem;
use Bonfire\Widgets\Types\Stats\Stats;
use Bonfire\Widgets\Types\Stats\StatsItem;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class ManagerTest extends TestCase
{
    public function testItemsListsEveryItemOfEveryCollectionOfEveryWidget()
    {
        $manager = (new Manager())
            ->createWidget(Stats::class, 'stats')
            ->createWidget(Charts::class, 'charts');

        $a = new StatsItem(['title' => 'A', 'id' => 'a']);
        $b = new StatsItem(['title' => 'B', 'id' => 'b']);
        $c = new ChartsItem(['title' => 'C', 'id' => 'c', 'type' => 'pie']);

        $manager->widget('stats')->createCollection('first')->addItem($a);
        $manager->widget('stats')->createCollection('second')->addItem($b);
        $manager->widget('charts')->createCollection('charts')->addItem($c);

        $this->assertSame([$a, $b, $c], $manager->items());
    }

    public function testItemsIsEmptyWithoutWidgets()
    {
        $this->assertSame([], (new Manager())->items());
    }
}
