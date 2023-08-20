<?php

namespace Tests\Menus;

use Bonfire\Menus\MenuCollection;
use Bonfire\Menus\MenuItem;
use Bonfire\Users\Models\UserModel;
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

    public function testHasVisibleItems()
    {
        // first create user:
        $user = fake(UserModel::class);
        /** @phpstan-ignore-next-line */
        $user->createEmailIdentity(['email' => 'foo@example.com', 'password' => 'alsdkfja;sldkfj']);
        /** @phpstan-ignore-next-line */
        $user->addGroup('admin');

        // immitate user login
        $response = $this->post(route_to('login'), [
            'email'    => 'foo@example.com',
            'password' => 'alsdkfja;sldkfj',
        ]);
        // check if login successfull
        $response->assertSessionHas('logged_in');

        // create menu collection to test
        $collection = new MenuCollection(['name' => 'Bar']);

        // add item that will not be visible to admin:
        $superadminMenu = new MenuItem([
            'title'           => 'Item not seen to admin',
            'namedRoute'      => 'user-list',
            'fontAwesomeIcon' => 'fas fa-user-cog',
            'permission'      => 'admin.settings',
        ]);
        $collection->addItem($superadminMenu);

        // test that collection does not have visible items: 
        $this->assertFalse($collection->hasVisibleItems());

        // add item that would be visible to admin
        $adminMenu = new MenuItem([
            'title'           => 'Item available to admin',
            'namedRoute'      => 'user-list',
            'fontAwesomeIcon' => 'fas fa-user',
            'permission'      => 'admin.access',
        ]);
        $collection->addItem($adminMenu);
        
        // test that collection now does have visible items: 
        $this->assertTrue($collection->hasVisibleItems());
    }
}
