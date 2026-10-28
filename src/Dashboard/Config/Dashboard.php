<?php

namespace Bonfire\Dashboard\Config;

use CodeIgniter\Config\BaseConfig;

class Dashboard extends BaseConfig
{
    public array $cells = [
        'Bonfire\Dashboard\DashboardCells::quickLinks',
        'Bonfire\Widgets\Cells\WidgetCells::stats',
        'Bonfire\Widgets\Cells\WidgetCells::logs',
        'Bonfire\Widgets\Cells\WidgetCells::charts',
    ];

    /**
     * The site default layout (global) or a user's own (context "user:<id>"),
     * in the format of Layout::toArray(). Null means derived from $cells.
     */
    public ?array $layout = null;
}
