<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Widgets;

use Bonfire\Widgets\Types\Charts\ChartsItem;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class ChartsItemScriptTest extends TestCase
{
    protected $refresh = true;

    /**
     * @param array<string, mixed> $settings
     */
    #[DataProvider('provideScriptReflectsTheTypesSettings')]
    public function testScriptReflectsTheTypesSettings(string $type, array $settings, string $expected)
    {
        foreach ($settings as $key => $value) {
            setting($key, $value);
        }

        $script = $this->normalise($this->chart($type)->getScript());

        $this->assertStringContainsString($expected, $script);
    }

    public static function provideScriptReflectsTheTypesSettings(): iterable
    {
        yield 'line, config defaults' => [
            'line', [],
            'const backgroundColor__ID = null; const borderColor__ID = null;'
            . " const Chart__ID = new Chart( document.getElementById('_ID'),"
            . " drawChart( data__ID, labels__ID, 'CHART', 'line', 0.1, backgroundColor__ID , borderColor__ID, null, true, true, null, true, 'bottom') );",
        ];

        yield 'line, options changed' => [
            'line',
            [
                'LineChart.line_tension'         => 0.5,
                'LineChart.line_showSubTitle'    => true,
                'LineChart.line_enableAnimation' => false,
                'LineChart.line_legendPosition'  => 'top',
            ],
            "'line', 0.5, backgroundColor__ID , borderColor__ID, null, null, true, true, true, 'top')",
        ];

        yield 'bar, config defaults' => [
            'bar', [],
            'const backgroundColor__ID = null; const borderColor__ID = null;'
            . " const Chart__ID = new Chart( document.getElementById('_ID'),"
            . " drawChart( data__ID, labels__ID, 'CHART', 'bar', null, backgroundColor__ID , borderColor__ID, null, true, true, null, true, 'bottom') );",
        ];

        yield 'bar, scheme' => [
            'bar', ['BarChart.bar_colorScheme' => 'Blues'],
            "const backgroundColor__ID = d3.schemeBlues[9]; const borderColor__ID = 'blues';",
        ];

        yield 'bar, switched off' => [
            'bar',
            ['BarChart.bar_showTitle' => false, 'BarChart.bar_showLegend' => false, 'BarChart.bar_enableAnimation' => false],
            "'bar', null, backgroundColor__ID , borderColor__ID, null, null, null, null, null, 'bottom')",
        ];

        yield 'doughnut, scheme' => [
            'doughnut', ['DoughnutChart.doughnut_colorScheme' => 'Greens', 'DoughnutChart.doughnut_legendPosition' => 'left'],
            "const backgroundColor__ID = d3.schemeGreens[9]; const borderColor__ID = 'Greens';",
        ];

        yield 'doughnut, legend position' => [
            'doughnut', ['DoughnutChart.doughnut_legendPosition' => 'left'],
            "'doughnut', null, backgroundColor__ID , borderColor__ID, null, true, true, null, true, 'left')",
        ];

        yield 'pie, scheme' => [
            'pie', ['PieChart.pie_colorScheme' => 'Reds', 'PieChart.pie_showTitle' => false],
            "const backgroundColor__ID = d3.schemeReds[9]; const borderColor__ID = 'Reds';",
        ];

        yield 'pie, title off' => [
            'pie', ['PieChart.pie_showTitle' => false],
            "'pie', null, backgroundColor__ID , borderColor__ID, null, true, null, null, true, 'bottom')",
        ];

        yield 'polarArea, scheme' => [
            'polarArea', ['PolarAreaChart.polarArea_colorScheme' => 'Purples'],
            "const backgroundColor__ID = d3.schemePurples[9]; const borderColor__ID = 'Purples';",
        ];

        yield 'polarArea, legend off' => [
            'polarArea', ['PolarAreaChart.polarArea_showLegend' => false],
            "'polarArea', null, backgroundColor__ID , borderColor__ID, null, true, true, null, null, 'bottom')",
        ];
    }

    public function testDataAndLabelsAreEmbedded()
    {
        $item = $this->chart('bar')->setData([3, 5])->setLabel(['a', 'b']);

        $script = $this->normalise($item->getScript());

        $this->assertStringContainsString('const data__ID = [3,5]; const labels__ID = ["a","b"];', $script);
    }

    private function chart(string $type): ChartsItem
    {
        return new ChartsItem(['title' => 'Chart', 'type' => $type, 'id' => 'chart1']);
    }

    private function normalise(string $script): string
    {
        return preg_replace(['/_\d{6,}/', '/\s+/'], ['_ID', ' '], $script);
    }
}
