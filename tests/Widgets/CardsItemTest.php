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

use Bonfire\Widgets\Types\Cards\CardsItem;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class CardsItemTest extends TestCase
{
    public function testBasicDetails()
    {
        $item = new CardsItem();
        $item->setTitle('Item A')
            ->setBgColor('bg-blue')
            ->setFaIcon('fa fa-users')
            ->setUrl('/example/foo')
            ->setValue('5');

        $this->assertSame('ITEM A', $item->title());
        $this->assertSame('bg-blue', $item->bgColor());
        $this->assertSame('fa fa-users', $item->faIcon());
        $this->assertSame('/example/foo', $item->url());
        $this->assertSame('5', $item->value());
        $this->assertSame(true, $item->userCanSee());
    }

    public function testConstructorFill()
    {
        $item = new CardsItem([
            'title'   => 'Item A',
            'bgColor' => 'bg-blue',
            'faIcon'  => 'fa fa-users',
            'url'     => '/example/foo',
            'value'   => 5,
        ]);

        $this->assertSame('ITEM A', $item->title());
        $this->assertSame('bg-blue', $item->bgColor());
        $this->assertSame('fa fa-users', $item->faIcon());
        $this->assertSame('/example/foo', $item->url());
        $this->assertSame('5', $item->value());
        $this->assertSame(true, $item->userCanSee());
    }
}
