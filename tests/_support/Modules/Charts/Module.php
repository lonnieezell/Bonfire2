<?php

namespace Tests\Support\Modules\Charts;

use Bonfire\Core\BaseModule;
use Bonfire\Widgets\Types\Charts\ChartsItem;

/**
 * A line and a bar chart for the tests, since Bonfire itself adds no charts.
 * Discovered once a test calls withTestCharts().
 */
class Module extends BaseModule
{
    public function initAdmin()
    {
        foreach (['line' => 'testLine', 'bar' => 'testBar'] as $type => $id) {
            $chart = new ChartsItem([
                'title'    => 'Test chart',
                'type'     => $type,
                'id'       => $id,
                'cssClass' => 'col-6',
                'module'   => 'Test charts',
            ]);
            $chart->addDataset('auth_groups_users', 'group', 'user_id');
            service('widgets')->widget('charts')->collection('charts')->addItem($chart);
        }
    }
}
