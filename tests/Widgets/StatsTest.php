<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Bonfire\Widgets;

use Bonfire\Widgets\Types\Stats\Stats;
use Bonfire\Widgets\Types\Stats\StatsCollection;
use Bonfire\Widgets\Types\Stats\StatsItem;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class StatsTest extends TestCase
{
    public function testWithItems()
    {
        $widget = new Stats();
        $item1  = new StatsItem(['title' => 'Item 1']);
        $item2  = new StatsItem(['title' => 'Item 2']);

        $widget->addItem($item1)
            ->addItem($item2);

        $items = $widget->items();

        $this->assertCount(2, $items);
        $this->assertSame('ITEM 1', $items[0]->title);
        $this->assertSame('ITEM 2', $items[1]->title);
    }

    public function testCreateCollection()
    {
        $menu = new Stats();

        $collection = $menu->createCollection('test');

        $this->assertInstanceOf(StatsCollection::class, $collection);
        $this->assertSame('test', $collection->name);
        $this->assertSame('test', $collection->name());
    }

    public function testCollectCreatesNewCollection()
    {
        $widget = new Stats();
        $item1  = new StatsItem(['title' => 'Item 1']);
        $item2  = new StatsItem(['title' => 'Item 2']);

        $collection = $widget->collect('test', [$item1, $item2]);

        $this->assertInstanceOf(StatsCollection::class, $collection);
        $this->assertCount(2, $collection->items());
    }

    public function testCollectUsesExistingCollection()
    {
        $widget   = new Stats();
        $item1    = new StatsItem(['title' => 'Item 1']);
        $item2    = new StatsItem(['title' => 'Item 2']);
        $collect1 = $widget->createCollection('test');

        $collection = $widget->collect('test', [$item1, $item2]);

        $this->assertInstanceOf(StatsCollection::class, $collection);
        $this->assertSame($collection, $collect1);
        $this->assertCount(2, $collection->items());
    }

    public function testGetCollection()
    {
        $widget   = new Stats();
        $collect1 = $widget->createCollection('test1');
        $collect2 = $widget->createCollection('test2');

        $found = $widget->collection('test2');

        $this->assertInstanceOf(StatsCollection::class, $found);
        $this->assertSame($collect2, $found);
    }
}
