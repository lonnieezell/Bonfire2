<?php

namespace Tests\Bonfire\Widgets;


use Bonfire\Libraries\Widgets\Charts\ChartsItem;
use Tests\Support\TestCase;

class ChartsItemTest extends TestCase
{
    public function testBasicDetails()
    {
        $item = new ChartsItem();
        $item->setTitle('Item A')
			->setType('line')
            ->setCssclass('col-3');

        $this->assertEquals('Item A', $item->title());
		$this->assertEquals('line', $item->type());
		$this->assertEquals('col-3', $item->cssClass());
    }

    public function testConstructorFill()
    {
        $item = new ChartsItem([
			'title' => 'Item A',
			'type' => 'line',
			'cssClass' => 'col-3',
		]);

		$this->assertEquals('Item A', $item->title());
		$this->assertEquals('line', $item->type());
		$this->assertEquals('col-3', $item->cssClass());
    }
}
