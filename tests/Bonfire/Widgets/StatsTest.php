<?php

namespace Tests\Bonfire\Widgets;

use Bonfire\Libraries\Widgets\Stats\Stats;
use Bonfire\Libraries\Widgets\Stats\StatsCollection;
use Bonfire\Libraries\Widgets\Stats\StatsItem;
use Tests\Support\TestCase;

class StatsTest extends TestCase
{
    public function testWithItems()
    {
        $widget = new Stats();
        $item1 = new StatsItem(['title' => 'Item 1']);
        $item2 = new StatsItem(['title' => 'Item 2']);

		$widget->addItem($item1)
            ->addItem($item2);

        $items = $widget->items();

        $this->assertCount(2, $items);
        $this->assertEquals('ITEM 1', $items[0]->title);
        $this->assertEquals('ITEM 2', $items[1]->title);
    }

	public function testCreateCollection()
	{
		$menu = new Stats();

		$collection = $menu->createCollection('test');

		$this->assertInstanceOf(StatsCollection::class, $collection);
		$this->assertEquals('test', $collection->name);
		$this->assertEquals('test', $collection->name());
	}

	public function testCollectCreatesNewCollection()
	{
		$widget = new Stats();
		$item1 = new StatsItem(['title' => 'Item 1']);
		$item2 = new StatsItem(['title' => 'Item 2']);

		$collection = $widget->collect('test', [$item1, $item2]);

		$this->assertInstanceOf(StatsCollection::class, $collection);
		$this->assertCount(2, $collection->items());
	}

	public function testCollectUsesExistingCollection()
	{
		$widget = new Stats();
		$item1 = new StatsItem(['title' => 'Item 1']);
		$item2 = new StatsItem(['title' => 'Item 2']);
		$collect1 = $widget->createCollection('test');

		$collection = $widget->collect('test', [$item1, $item2]);

		$this->assertInstanceOf(StatsCollection::class, $collection);
		$this->assertSame($collection, $collect1);
		$this->assertCount(2, $collection->items());
	}

	public function testGetCollection()
	{
		$widget = new Stats();
		$collect1 = $widget->createCollection('test1');
		$collect2 = $widget->createCollection('test2');

		$found = $widget->collection('test2');

		$this->assertInstanceOf(StatsCollection::class, $found);
		$this->assertSame($collect2, $found);
	}
}
