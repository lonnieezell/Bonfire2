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
use Bonfire\Dashboard\WidgetValues;

/**
 * An item of the "logs" widget, which renders itself.
 */
class LogWidget extends ItemWidget
{
    public function title(): string
    {
        return method_exists($this->item, 'title') ? (string) $this->item->title() : '';
    }

    public function icon(): string
    {
        return 'fas fa-file-lines';
    }

    public function description(): string
    {
        return lang('Bonfire.logCard');
    }

    public function group(): string
    {
        return $this->moduleOr('charts');
    }

    public function sizes(): SizeRange
    {
        return new SizeRange(Size::Full, Size::Half);
    }

    public function fields(): array
    {
        return [];
    }

    public function render(WidgetValues $values): string
    {
        return method_exists($this->item, 'render') ? (string) $this->item->render() : '';
    }
}
