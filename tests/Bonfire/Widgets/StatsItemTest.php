<?php

namespace Tests\Bonfire\Widgets;

use Bonfire\Libraries\Widgets\Stats\StatsItem;
use Tests\Support\TestCase;

class StatsItemTest extends TestCase
{
    public function testBasicDetails()
    {
        $item = new StatsItem();
        $item->setTitle('Item A')
			->setBgColor('bg-blue')
			->setFaIcon('fa fa-users')
            ->setUrl('/example/foo')
			->setValue(5);

        $this->assertEquals('ITEM A', $item->title());
		$this->assertEquals('bg-blue', $item->bgColor());
		$this->assertEquals('fa fa-users', $item->faIcon());
		$this->assertEquals('/example/foo', $item->url());
        $this->assertEquals(5, $item->value());
    }

    public function testConstructorFill()
    {
        $item = new StatsItem([
			'title' => 'Item A',
			'bgColor' => 'bg-blue',
			'faIcon' => 'fa fa-users',
			'url' => '/example/foo',
			'value' => 5,
		]);

		$this->assertEquals('ITEM A', $item->title());
		$this->assertEquals('bg-blue', $item->bgColor());
		$this->assertEquals('fa fa-users', $item->faIcon());
		$this->assertEquals('/example/foo', $item->url());
		$this->assertEquals(5, $item->value());
    }
}
