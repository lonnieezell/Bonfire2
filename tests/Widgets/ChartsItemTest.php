<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Bonfire\Widgets;

use Bonfire\Widgets\Types\Charts\ChartsItem;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class ChartsItemTest extends TestCase
{
    public function testBasicDetails()
    {
        $item = new ChartsItem();
        $item->setTitle('Item A')
            ->setType('line')
            ->setCssclass('col-3');

        $this->assertSame('Item A', $item->title());
        $this->assertSame('line', $item->type());
        $this->assertSame('col-3', $item->cssClass());
    }

    public function testConstructorFill()
    {
        $item = new ChartsItem([
            'title'    => 'Item A',
            'type'     => 'line',
            'cssClass' => 'col-3',
        ]);

        $this->assertSame('Item A', $item->title());
        $this->assertSame('line', $item->type());
        $this->assertSame('col-3', $item->cssClass());
    }
}
