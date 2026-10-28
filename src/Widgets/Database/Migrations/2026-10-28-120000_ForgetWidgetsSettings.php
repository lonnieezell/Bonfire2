<?php

namespace Bonfire\Widgets\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Database;

/**
 * Forgets what Settings → Widgets stored: each item's enabled switch
 * (Stats.Stats_<id>, ...) and the site-wide widget defaults
 * (BarChart.bar_showTitle, ...). Nothing reads them since the page was
 * removed, and their Config classes are gone.
 */
class ForgetWidgetsSettings extends Migration
{
    private const CLASSES = ['Stats', 'BarChart', 'DoughnutChart', 'LineChart', 'PieChart', 'PolarAreaChart', 'LogActivity'];

    public function up()
    {
        $config = config('Settings')->database;

        Database::connect($config['group'] ?? null)->table($config['table'] ?? 'settings')
            ->whereIn('class', array_map(static fn (string $class) => 'Bonfire\Widgets\Config\\' . $class, self::CLASSES))
            ->delete();
    }

    public function down()
    {
        // The forgotten values are not kept
    }
}
