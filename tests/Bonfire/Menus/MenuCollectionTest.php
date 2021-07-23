<?php

namespace Tests\Bonfire\Menus;

use Bonfire\Libraries\Menus\MenuCollection;
use Bonfire\Libraries\Menus\MenuItem;
use Tests\Support\TestCase;

class MenuCollectionTest extends TestCase
{
    public function testExtendsMenuItem()
    {
        $item = new MenuCollection([
             'title' => 'Item A',
             'url' => 'example.com/foo?bar=baz',
             'altText' => 'Alternate A',
             'weight' => 5,
             'iconUrl' => 'example.com/img/foo.jpg'
         ]);

        $this->assertEquals('Item A', $item->title());
        $this->assertEquals('/example.com/foo?bar=baz', $item->url());
        $this->assertEquals('Alternate A', $item->altText());
        $this->assertEquals('<img href="/example.com/img/foo.jpg" alt="Item A" />', $item->icon());
        $this->assertEquals(5, $item->weight());
    }

    public function testNames()
    {
        $collection = new MenuCollection();
        $this->assertNull($collection->name());

        $collection = new MenuCollection(['name' => 'Foo']);
        $this->assertEquals('Foo', $collection->name());

        $collection = new MenuCollection();
        $collection->setName('Foo');
        $this->assertEquals('Foo', $collection->name());
    }

    public function testWithItem()
    {
        $collection = new MenuCollection(['name' => 'Foo']);
        $item1 = new MenuItem(['title' => 'Item 1']);
        $item2 = new MenuItem(['title' => 'Item 2']);

        $collection->addItem($item1);
        $collection->addItem($item2);

        $items = $collection->items();

        $this->assertCount(2, $items);
        $this->assertEquals('Item 1', $items[0]->title);
        $this->assertEquals('Item 2', $items[1]->title);

        $collection->removeItem('Item 1');

        $items = $collection->items();

        $this->assertCount(1, $items);
        $this->assertEquals('Item 2', $items[0]->title);

        $collection->removeAllItems();

        $items = $collection->items();
        $this->assertEquals([], $items);
    }

    public function testAddItems()
    {
        $collection = new MenuCollection(['name' => 'Foo']);
        $item1 = new MenuItem(['title' => 'Item 1']);
        $item2 = new MenuItem(['title' => 'Item 2']);

        $collection->addItems([$item1, $item2]);

        $items = $collection->items();

        $this->assertCount(2, $items);
        $this->assertEquals('Item 1', $items[0]->title);
        $this->assertEquals('Item 2', $items[1]->title);
    }

    public function testItemWeightsGetSorted()
    {
        $collection = new MenuCollection(['name' => 'Foo']);
        $item1 = new MenuItem(['title' => 'Item 1', 'weight' => 2]);
        $item2 = new MenuItem(['title' => 'Item 2', 'weight' => 1]);

        $collection->addItems([$item1, $item2]);

        $items = $collection->items();

        $this->assertCount(2, $items);
        $this->assertEquals('Item 2', $items[0]->title);
        $this->assertEquals('Item 1', $items[1]->title);
    }
}
