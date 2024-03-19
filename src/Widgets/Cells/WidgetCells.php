<?php

namespace Bonfire\Widgets\Cells;

use Bonfire\View\Metadata;

class WidgetCells
{
    /**
     * Displays the stat blocks in the admin dashboard.
     */
    public function stats()
    {
        $widgets = service('widgets');

        return view('Bonfire\Widgets\Views\Cells\stats', [
            'stats'   => $widgets->widget('stats')->items(),
            'manager' => $widgets->manager(),
        ]);
    }

    /**
     * Displays the card blocks in the admin dashboard.
     */
    public function cards()
    {
        $widgets = service('widgets');

        return view('Bonfire\Widgets\Views\Cells\cards', [
            'cards'   => $widgets->widget('cards')->items(),
            'manager' => $widgets->manager(),
        ]);
    }

    /**
     * Displays the chart blocks in the admin dashboard.
     */
    public function charts()
    {
        /** @var Metadata */
        $meta = service('viewMeta');
        $meta->addScript(['src' => 'https://cdn.jsdelivr.net/npm/d3-color@3']);
        $meta->addScript(['src' => 'https://cdn.jsdelivr.net/npm/d3-interpolate@3']);
        $meta->addScript(['src' => 'https://cdn.jsdelivr.net/npm/d3-scale-chromatic@3']);
        $meta->addScript([
            'src'            => 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.6.2/chart.min.js',
            'integrity'      => 'sha512-tMabqarPtykgDtdtSqCL3uLVM0gS1ZkUAVhRFu1vSEFgvB73niFQWJuvviDyBGBH22Lcau4rHB5p2K2T0Xvr6Q==',
            'crossorigin'    => 'anonymous',
            'referrerpolicy' => 'no-referrer',
        ]);
        $meta->addScript(['src' => asset('admin/js/chart.js', 'js')]);
        $meta->addRawScript(view('Bonfire\Widgets\Views\Cells\scripts', [
            'charts'  => service('widgets')->widget('charts')->items(),
            'manager' => service('widgets')->manager(),
        ]));

        $widgets = service('widgets');

        return view('Bonfire\Widgets\Views\Cells\charts', [
            'charts'  => $widgets->widget('charts')->items(),
            'manager' => $widgets->manager(),
        ]);
    }
}
