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

use Bonfire\Widgets\Types\Cards\CardsCollection;
use Bonfire\Widgets\Types\Cards\CardsItem;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class CardsCollectionTest extends TestCase
{
    public function testExtendsCardsItem()
    {
        $item = new CardsCollection([
            'bgColor' => 'bg-blue',
            'title'   => 'Item A',
            'value'   => 15,
            'url'     => '/example/foo',
            'faIcon'  => 'fa fa-users',
        ]);

        $this->assertSame('bg-blue', $item->bgColor());
        $this->assertSame('ITEM A', $item->title());
        $this->assertSame('15', $item->value());
        $this->assertSame('/example/foo', $item->url());
        $this->assertSame('fa fa-users', $item->faIcon());
    }

    public function testNames()
    {
        $collection = new CardsCollection();
        $this->assertSame('', $collection->name());

        $collection = new CardsCollection(['name' => 'Foo']);
        $this->assertSame('Foo', $collection->name());

        $collection = new CardsCollection();
        $collection->setName('Foo');
        $this->assertSame('Foo', $collection->name());
    }

    public function testWithItem()
    {
        $collection = new CardsCollection(['name' => 'Foo']);
        $item1      = new CardsItem(['title' => 'Item 1']);
        $item2      = new CardsItem(['title' => 'Item 2']);

        $collection->addItem($item1);
        $collection->addItem($item2);

        $items = $collection->items();

        $this->assertCount(2, $items);
        $this->assertSame('ITEM 1', $items[0]->title);
        $this->assertSame('ITEM 2', $items[1]->title);

        $collection->removeItem('ITEM 1');

        $items = $collection->items();

        $this->assertCount(1, $items);
        $this->assertSame('ITEM 2', $items[0]->title);

        $collection->removeAllItems();

        $items = $collection->items();
        $this->assertSame([], $items);
    }

    public function testAddItems()
    {
        $collection = new CardsCollection(['name' => 'Foo']);
        $item1      = new CardsItem(['title' => 'Item 1']);
        $item2      = new CardsItem(['title' => 'Item 2']);

        $collection->addItems([$item1, $item2]);

        $items = $collection->items();

        $this->assertCount(2, $items);
        $this->assertSame('ITEM 1', $items[0]->title);
        $this->assertSame('ITEM 2', $items[1]->title);
    }
}
