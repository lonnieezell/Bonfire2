<?php

namespace Tests\Bonfire\Widgets;

use Bonfire\Libraries\Widgets\Stats\StatsCollection;
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

}
