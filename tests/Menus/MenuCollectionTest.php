<?php

namespace Tests\Menus;

use Bonfire\Menus\MenuCollection;
use Bonfire\Menus\MenuItem;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class MenuCollectionTest extends TestCase
{
    public function testExtendsMenuItem()
    {
        $item = new MenuCollection([
            'title'   => 'Item A',
            'url'     => 'example.com/foo?bar=baz',
            'altText' => 'Alternate A',
            'weight'  => 5,
            'iconUrl' => 'example.com/img/foo.jpg',
        ]);

        $this->assertSame('Item A', $item->title());
        $this->assertSame('/example.com/foo?bar=baz', $item->url());
        $this->assertSame('Alternate A', $item->altText());
        $this->assertSame('<img href="/example.com/img/foo.jpg" alt="Item A" />', $item->icon());
        $this->assertSame(5, $item->weight());
    }

    public function testNames()
    {
        $collection = new MenuCollection();
        $this->assertNull($collection->name());

        $collection = new MenuCollection(['name' => 'Foo']);
        $this->assertSame('Foo', $collection->name());

        $collection = new MenuCollection();
        $collection->setName('Foo');
        $this->assertSame('Foo', $collection->name());
    }

    public function testWithItem()
    {
        $collection = new MenuCollection(['name' => 'Foo']);
        $item1      = new MenuItem(['title' => 'Item 1']);
        $item2      = new MenuItem(['title' => 'Item 2']);

        $collection->addItem($item1);
        $collection->addItem($item2);

        $items = $collection->items();

        $this->assertCount(2, $items);
        $this->assertSame('Item 1', $items[0]->title);
        $this->assertSame('Item 2', $items[1]->title);

        $collection->removeItem('Item 1');

        $items = $collection->items();

        $this->assertCount(1, $items);
        $this->assertSame('Item 2', $items[0]->title);

        $collection->removeAllItems();

        $items = $collection->items();
        $this->assertSame([], $items);
    }

    public function testAddItems()
    {
        $collection = new MenuCollection(['name' => 'Foo']);
        $item1      = new MenuItem(['title' => 'Item 1']);
        $item2      = new MenuItem(['title' => 'Item 2']);

        $collection->addItems([$item1, $item2]);

        $items = $collection->items();

        $this->assertCount(2, $items);
        $this->assertSame('Item 1', $items[0]->title);
        $this->assertSame('Item 2', $items[1]->title);
    }

    public function testItemWeightsGetSorted()
    {
        $collection = new MenuCollection(['name' => 'Foo']);
        $item1      = new MenuItem(['title' => 'Item 1', 'weight' => 2]);
        $item2      = new MenuItem(['title' => 'Item 2', 'weight' => 1]);

        $collection->addItems([$item1, $item2]);

        $items = $collection->items();

        $this->assertCount(2, $items);
        $this->assertSame('Item 2', $items[0]->title);
        $this->assertSame('Item 1', $items[1]->title);
    }
}
