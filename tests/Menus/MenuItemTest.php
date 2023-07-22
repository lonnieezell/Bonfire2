<?php

namespace Tests\Menus;

use Bonfire\Menus\MenuItem;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class MenuItemTest extends TestCase
{
    public function testBasicDetails()
    {
        $item = new MenuItem();
        $item->setTitle('Item A')
            ->setUrl('example.com/foo?bar=baz')
            ->setAltText('Alternate A')
            ->setWeight(5)
            ->setIconUrl('example.com/img/foo.jpg');

        $this->assertSame('Item A', $item->title());
        $this->assertSame('/example.com/foo?bar=baz', $item->url());
        $this->assertSame('Alternate A', $item->altText());
        $this->assertSame('<img href="/example.com/img/foo.jpg" alt="Item A" />', $item->icon());
        $this->assertSame(5, $item->weight());
    }

    public function testConstructorFill()
    {
        $item = new MenuItem([
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

    public function testBuildsFontAwesomeTag()
    {
        $item = new MenuItem();

        $this->assertSame('', $item->icon());

        $item->setFontAwesomeIcon('fa-envelope');

        $this->assertSame('<i class="fa-envelope"></i>', $item->icon());
    }

    public function testBuildsFontAwesomeTagWithExtraClass()
    {
        $item = new MenuItem();

        $this->assertSame('', $item->icon());

        $item->setFontAwesomeIcon('fa-envelope');

        $this->assertSame('<i class="fa-envelope extra"></i>', $item->icon('extra'));
    }

    public function testPrefersFontAwesomeOverImg()
    {
        $item = new MenuItem();
        $item->setFontAwesomeIcon('fa-envelope')
            ->setIconUrl('example.com/img/foo.jpg');

        $this->assertSame('<i class="fa-envelope"></i>', $item->icon());
    }

    public function testImageTagWithExtraClass()
    {
        $item = new MenuItem();
        $item->setTitle('Item A')
            ->setIconUrl('example.com/img/foo.jpg');

        $this->assertSame('<img href="/example.com/img/foo.jpg" alt="Item A" class="extra" />', $item->icon('extra'));
    }

    public function testWithNamedRoutes()
    {
        $routes = service('routes');
        $routes->get('home-sweet-home', 'HomeController::index', ['as' => 'home']);

        $item = new MenuItem();
        $item->setNamedRoute('home');

        $this->assertSame(site_url('/home-sweet-home'), $item->url());
    }

    public function testPropertyGetter()
    {
        $item = new MenuItem(['title' => 'Item 1', 'fontAwesomeIcon' => 'fa-envelope']);

        $this->assertSame('Item 1', $item->title);
        $this->assertSame('<i class="fa-envelope"></i>', $item->icon);
    }
}
