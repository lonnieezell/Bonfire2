<?php

namespace Tests\Bonfire\Widgets;

use Bonfire\Libraries\Widgets\Stats\StatsCollection;
use Bonfire\Libraries\Widgets\Stats\StatsItem;
use Tests\Support\TestCase;

class StatsCollectionTest extends TestCase
{
	public function testExtendsMenuItem()
	{
		$item = new StatsCollection([
			'bgColor' => 'bg-blue',
			'title' => 'Item A',
			'value' => 15,
			'url' => '/example/foo',
			'faIcon' => 'fa fa-users',
		]);

		$this->assertEquals('bg-blue', $item->bgColor());
		$this->assertEquals('ITEM A', $item->title());
		$this->assertEquals(15, $item->value());
		$this->assertEquals('/example/foo', $item->url());
		$this->assertEquals('fa fa-users', $item->faIcon());
	}

	public function testNames()
	{
		$collection = new StatsCollection();
		$this->assertNull($collection->name());

		$collection = new StatsCollection(['name' => 'Foo']);
		$this->assertEquals('Foo', $collection->name());

		$collection = new StatsCollection();
		$collection->setName('Foo');
		$this->assertEquals('Foo', $collection->name());
	}

	public function testWithItem()
	{
		$collection = new StatsCollection(['name' => 'Foo']);
		$item1 = new StatsItem(['title' => 'Item 1']);
		$item2 = new StatsItem(['title' => 'Item 2']);

		$collection->addItem($item1);
		$collection->addItem($item2);

		$items = $collection->items();

		$this->assertCount(2, $items);
		$this->assertEquals('ITEM 1', $items[0]->title);
		$this->assertEquals('ITEM 2', $items[1]->title);

		$collection->removeItem('ITEM 1');

		$items = $collection->items();

		$this->assertCount(1, $items);
		$this->assertEquals('ITEM 2', $items[0]->title);

		$collection->removeAllItems();

		$items = $collection->items();
		$this->assertEquals([], $items);
	}

	public function testAddItems()
	{
		$collection = new StatsCollection(['name' => 'Foo']);
		$item1 = new StatsItem(['title' => 'Item 1']);
		$item2 = new StatsItem(['title' => 'Item 2']);

		$collection->addItems([$item1, $item2]);

		$items = $collection->items();

		$this->assertCount(2, $items);
		$this->assertEquals('ITEM 1', $items[0]->title);
		$this->assertEquals('ITEM 2', $items[1]->title);
	}
}
