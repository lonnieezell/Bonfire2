<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Support\Libraries;

use Bonfire\Dashboard\DashboardWidget;
use Bonfire\Dashboard\Size;
use Bonfire\Dashboard\SizeRange;
use Bonfire\Dashboard\WidgetField;
use Bonfire\Dashboard\WidgetValues;

/**
 * A native dashboard widget that counts how often it is rendered, and
 * keeps the settings values it was last rendered with.
 */
final class TestWidget implements DashboardWidget
{
    public int $renders          = 0;
    public ?WidgetValues $values = null;

    /**
     * @param list<WidgetField> $fields
     */
    public function __construct(
        private readonly string $id,
        private readonly ?string $permission = null,
        private readonly SizeRange $sizes = new SizeRange(Size::Half),
        private readonly array $fields = [],
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function title(): string
    {
        return 'Test widget';
    }

    public function icon(): string
    {
        return 'fas fa-flask';
    }

    public function group(): string
    {
        return 'custom';
    }

    public function description(): string
    {
        return '';
    }

    public function sizes(): SizeRange
    {
        return $this->sizes;
    }

    public function permission(): ?string
    {
        return $this->permission;
    }

    public function fields(): array
    {
        return $this->fields;
    }

    public function render(WidgetValues $values): string
    {
        $this->renders++;
        $this->values = $values;

        return '<p data-test-widget="' . esc($this->id, 'attr') . '"></p>';
    }
}
