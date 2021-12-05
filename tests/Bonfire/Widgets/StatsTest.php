<?php

namespace Tests\Bonfire\Menus;


use Bonfire\Libraries\Widgets\Stats\Stats;
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


}
