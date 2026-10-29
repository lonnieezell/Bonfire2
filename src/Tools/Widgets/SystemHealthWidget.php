<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Tools\Widgets;

use Bonfire\Dashboard\DashboardWidget;
use Bonfire\Dashboard\Size;
use Bonfire\Dashboard\SizeRange;
use Bonfire\Dashboard\WidgetField;
use Bonfire\Dashboard\WidgetValues;
use Bonfire\Tools\Libraries\SystemHealth;

/**
 * The "System health" dashboard widget: what is wrong with the install's
 * environment, such as folders that can't be written to, if anything.
 */
class SystemHealthWidget implements DashboardWidget
{
    public const ID = 'tools:systemHealth';

    public function __construct(private readonly SystemHealth $health = new SystemHealth())
    {
    }

    public function id(): string
    {
        return self::ID;
    }

    public function title(): string
    {
        return lang('Tools.systemHealth');
    }

    public function icon(): string
    {
        return 'fas fa-heart-pulse';
    }

    public function group(): string
    {
        return lang('Bonfire.toolsModTitle');
    }

    public function description(): string
    {
        return lang('Tools.systemHealthCard');
    }

    public function sizes(): SizeRange
    {
        return new SizeRange(Size::Half, Size::Third);
    }

    public function permission(): ?string
    {
        return 'admin.settings';
    }

    public function fields(): array
    {
        return [
            WidgetField::number('maxLoad', lang('Tools.systemHealthMaxLoad'), default: 4.0, min: 0.5, max: 100, step: 0.5),
        ];
    }

    public function render(WidgetValues $values): string
    {
        return view('Bonfire\Tools\Views\_system_health', [
            'title'    => $this->title(),
            'problems' => $this->health->problems((float) $values->get('maxLoad')),
        ]);
    }
}
