<?php

namespace Bonfire\Widgets\Cells;

use Bonfire\View\Metadata;
use Bonfire\Widgets\Types\Charts\ChartsItem;

class WidgetCells
{
    /**
     * Displays the stat blocks in the admin dashboard.
     */
    public function stats()
    {
        $widgets = service('widgets');

        return view('Bonfire\Widgets\Views\Cells\stats', [
            'stats' => $widgets->widget('stats')->items(),
        ]);
    }

    /**
     * Displays the log widgets in the admin dashboard.
     */
    public function logs()
    {
        return view('Bonfire\Widgets\Views\Cells\logs', [
            'logs' => service('widgets')->widget('logs')->items(),
        ]);
    }

    /**
     * Displays the chart blocks in the admin dashboard.
     */
    public function charts()
    {
        /** @var Metadata */
        $meta = service('viewMeta');
        ChartsItem::addAssets($meta);
        $meta->addRawScript(view('Bonfire\Widgets\Views\Cells\scripts', [
            'charts' => service('widgets')->widget('charts')->items(),
        ]));

        $widgets = service('widgets');

        return view('Bonfire\Widgets\Views\Cells\charts', [
            'charts' => $widgets->widget('charts')->items(),
        ]);
    }
}
