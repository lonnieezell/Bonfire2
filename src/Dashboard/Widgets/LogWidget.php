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

/**
 * An item of the "logs" widget, such as Log activity, which renders itself.
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

    public function group(): string
    {
        return 'charts';
    }

    public function sizes(): SizeRange
    {
        return new SizeRange(Size::Full, Size::Medium);
    }

    public function render(): string
    {
        return method_exists($this->item, 'render') ? (string) $this->item->render() : '';
    }
}
