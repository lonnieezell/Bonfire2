<?php

namespace Tests\Bonfire\Menus;

use Bonfire\Libraries\Menus\Menu;
use Bonfire\Libraries\Menus\MenuCollection;
use Bonfire\Libraries\Menus\MenuItem;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class MenuTest extends TestCase
{
    public function testWithItems()
    {
        $menu  = new Menu();
        $item1 = new MenuItem(['title' => 'Item 1']);
        $item2 = new MenuItem(['title' => 'Item 2']);

        $menu->addItem($item1)
            ->addItem($item2);

        $items = $menu->items();

        $this->assertCount(2, $items);
        $this->assertSame('Item 1', $items[0]->title);
        $this->assertSame('Item 2', $items[1]->title);
    }

    public function testCreateCollection()
    {
        $menu = new Menu();

        $collection = $menu->createCollection('settings-collection', 'Settings');

        $this->assertInstanceOf(MenuCollection::class, $collection);
        $this->assertSame('settings-collection', $collection->name);
        $this->assertSame('settings-collection', $collection->name());
        $this->assertSame('Settings', $collection->title);
        $this->assertSame('Settings', $collection->title());
    }

    public function testCollectCreatesNewCollection()
    {
        $menu  = new Menu();
        $item1 = new MenuItem(['title' => 'Item 1']);
        $item2 = new MenuItem(['title' => 'Item 2']);

        $collection = $menu->collect('settings', [$item1, $item2]);

        $this->assertInstanceOf(MenuCollection::class, $collection);
        $this->assertCount(2, $collection->items());
    }

    public function testCollectUsesExistingCollection()
    {
        $menu     = new Menu();
        $item1    = new MenuItem(['title' => 'Item 1']);
        $item2    = new MenuItem(['title' => 'Item 2']);
        $collect1 = $menu->createCollection('settings', 'Settings');

        $collection = $menu->collect('settings', [$item1, $item2]);

        $this->assertInstanceOf(MenuCollection::class, $collection);
        $this->assertSame($collection, $collect1);
        $this->assertCount(2, $collection->items());
    }

    public function testGetCollection()
    {
        $menu     = new Menu();
        $collect1 = $menu->createCollection('collect-1', 'Collect 1');
        $collect2 = $menu->createCollection('collect-2', 'Collect 2');

        $found = $menu->collection('collect-2');

        $this->assertInstanceOf(MenuCollection::class, $found);
        $this->assertSame($collect2, $found);
    }
}
