<?php

namespace Tests\Bonfire\Widgets;

use Bonfire\Libraries\Widgets\Charts\Charts;
use Bonfire\Libraries\Widgets\Charts\ChartsCollection;
use Bonfire\Libraries\Widgets\Charts\ChartsItem;
use Tests\Support\TestCase;

class ChartsTest extends TestCase
{
    public function testWithItems()
    {
        $widget = new Charts();
        $item1 = new ChartsItem(['title' => 'Item 1']);
        $item2 = new ChartsItem(['title' => 'Item 2']);

		$widget->addItem($item1)
            ->addItem($item2);

        $items = $widget->items();

        $this->assertCount(2, $items);
        $this->assertEquals('Item 1', $items[0]->title());
        $this->assertEquals('Item 2', $items[1]->title());
    }

	public function testCreateCollection()
	{
		$menu = new Charts();

		$collection = $menu->createCollection('test');

		$this->assertInstanceOf(ChartsCollection::class, $collection);
		$this->assertEquals('test', $collection->name);
		$this->assertEquals('test', $collection->name());
	}

	public function testCollectCreatesNewCollection()
	{
		$widget = new Charts();
		$item1 = new ChartsItem(['title' => 'Item 1']);
		$item2 = new ChartsItem(['title' => 'Item 2']);

		$collection = $widget->collect('test', [$item1, $item2]);

		$this->assertInstanceOf(ChartsCollection::class, $collection);
		$this->assertCount(2, $collection->items());
	}

	public function testCollectUsesExistingCollection()
	{
		$widget = new Charts();
		$item1 = new ChartsItem(['title' => 'Item 1']);
		$item2 = new ChartsItem(['title' => 'Item 2']);
		$collect1 = $widget->createCollection('test');

		$collection = $widget->collect('test', [$item1, $item2]);

		$this->assertInstanceOf(ChartsCollection::class, $collection);
		$this->assertSame($collection, $collect1);
		$this->assertCount(2, $collection->items());
	}

	public function testGetCollection()
	{
		$widget = new Charts();
		$collect1 = $widget->createCollection('test1');
		$collect2 = $widget->createCollection('test2');

		$found = $widget->collection('test2');

		$this->assertInstanceOf(ChartsCollection::class, $found);
		$this->assertSame($collect2, $found);
	}
}
