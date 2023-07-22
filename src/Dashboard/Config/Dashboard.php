<?php

namespace Bonfire\Dashboard\Config;

use CodeIgniter\Config\BaseConfig;

class Dashboard extends BaseConfig
{
    public array $cells = [
        'Bonfire\Dashboard\DashboardCells::quickLinks',
        'Bonfire\Widgets\Cells\WidgetCells::stats',
        'Bonfire\Widgets\Cells\WidgetCells::charts',
    ];
}
