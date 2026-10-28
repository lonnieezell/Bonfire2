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

use Bonfire\Dashboard\DashboardWidget;
use Bonfire\Dashboard\Size;
use Bonfire\Dashboard\SizeRange;

/**
 * A view cell listed in Dashboard.cells, as "Class::method".
 */
class CellWidget implements DashboardWidget
{
    public function __construct(private readonly string $alias)
    {
    }

    public function id(): string
    {
        return 'cell:' . $this->alias;
    }

    /**
     * The method name in words: quickLinks becomes "Quick Links".
     */
    public function title(): string
    {
        $method = explode('::', $this->alias)[1] ?? $this->alias;

        return ucwords((string) preg_replace('/(?<!^)[A-Z]/', ' $0', $method));
    }

    public function icon(): string
    {
        return 'fas fa-puzzle-piece';
    }

    public function group(): string
    {
        return 'custom';
    }

    public function description(): string
    {
        return lang('Bonfire.customCard');
    }

    public function sizes(): SizeRange
    {
        return new SizeRange(Size::Full);
    }

    public function permission(): ?string
    {
        return null;
    }

    public function render(): string
    {
        [$class, $method] = explode('::', $this->alias);

        return (string) (new $class())->{$method}();
    }
}
