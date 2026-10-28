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

/**
 * A native dashboard widget that counts how often it is rendered.
 */
final class TestWidget implements DashboardWidget
{
    public int $renders = 0;

    public function __construct(
        private readonly string $id,
        private readonly ?string $permission = null,
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
        return new SizeRange(Size::Medium);
    }

    public function permission(): ?string
    {
        return $this->permission;
    }

    public function render(): string
    {
        $this->renders++;

        return '<p data-test-widget="' . esc($this->id, 'attr') . '"></p>';
    }
}
