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

use Bonfire\Widgets\Types\Charts\ChartsCollection;
use Bonfire\Widgets\Types\Charts\ChartsItem;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class ChartsCollectionTest extends TestCase
{
    public function testExtendsChartsItem()
    {
        $item = new ChartsCollection([
            'title'    => 'Item A',
            'type'     => 'line',
            'cssClass' => 'col-3',
        ]);

        $this->assertSame('Item A', $item->title());
        $this->assertSame('line', $item->type());
        $this->assertSame('col-3', $item->cssClass());
    }

    public function testTitles()
    {
        $collection = new ChartsCollection();
        $this->assertNull($collection->title());

        $collection = new ChartsCollection(['title' => 'Foo']);
        $this->assertSame('Foo', $collection->title());

        $collection = new ChartsCollection();
        $collection->setTitle('Foo');
        $this->assertSame('Foo', $collection->title());
    }

    public function testWithItem()
    {
        $chart_collection = new ChartsCollection(['name' => 'Foo']);
        $item1            = new ChartsItem(['title' => 'Item 1']);
        $item2            = new ChartsItem(['title' => 'Item 2']);

        $chart_collection->addItem($item1);
        $chart_collection->addItem($item2);

        $items = $chart_collection->items();

        $this->assertCount(2, $items);
        $this->assertSame('Item 1', $items[0]->title());
        $this->assertSame('Item 2', $items[1]->title());

        $chart_collection->removeItem('Item 1');

        $items = $chart_collection->items();

        $this->assertCount(1, $items);
        $this->assertSame('Item 2', $items[0]->title());

        $chart_collection->removeAllItems();

        $items = $chart_collection->items();
        $this->assertSame([], $items);
    }

    public function testAddItems()
    {
        $chart_collection = new ChartsCollection(['name' => 'Foo']);
        $item1            = new ChartsItem(['title' => 'Item 1']);
        $item2            = new ChartsItem(['title' => 'Item 2']);

        $chart_collection->addItems([$item1, $item2]);

        $items = $chart_collection->items();

        $this->assertCount(2, $items);
        $this->assertSame('Item 1', $items[0]->title());
        $this->assertSame('Item 2', $items[1]->title());
    }
}
