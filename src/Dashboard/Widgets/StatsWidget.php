<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Dashboard\Widgets;

use Bonfire\Dashboard\Size;
use Bonfire\Dashboard\SizeRange;
use Bonfire\Widgets\Types\Stats\StatsItem;

/**
 * A StatsItem as a stat card.
 *
 * @property StatsItem $item
 */
class StatsWidget extends ItemWidget
{
    public function title(): string
    {
        return (string) $this->item->title();
    }

    public function icon(): string
    {
        return (string) $this->item->faIcon();
    }

    public function description(): string
    {
        return lang('Bonfire.statCard');
    }

    public function group(): string
    {
        return 'stats';
    }

    public function sizes(): SizeRange
    {
        return new SizeRange(Size::Quarter);
    }

    protected function settingsTab(): ?string
    {
        return 'stats';
    }

    public function render(): string
    {
        return view('Bonfire\Widgets\Views\Cells\_stat', ['stat' => $this->item]);
    }
}
