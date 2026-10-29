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
        $script = $this->normalise($this->chart($type)->getScript($settings));

        $this->assertStringContainsString($expected, $script);
    }

    public static function provideScriptReflectsTheTypesSettings(): iterable
    {
        yield 'line, defaults' => [
            'line', [],
            'const backgroundColor__ID = null; const borderColor__ID = null;'
            . " const Chart__ID = new Chart( document.getElementById('_ID'),"
            . " drawChart( data__ID, labels__ID, 'CHART', 'line', 0.1, backgroundColor__ID , borderColor__ID, null, true, true, null, true, 'bottom') );",
        ];

        yield 'line, options changed' => [
            'line',
            [
                'tension'         => 0.5,
                'enableAnimation' => false,
                'legendPosition'  => 'top',
            ],
            "'line', 0.5, backgroundColor__ID , borderColor__ID, null, null, true, null, true, 'top')",
        ];

        yield 'bar, defaults' => [
            'bar', [],
            'const backgroundColor__ID = null; const borderColor__ID = null;'
            . " const Chart__ID = new Chart( document.getElementById('_ID'),"
            . " drawChart( data__ID, labels__ID, 'CHART', 'bar', null, backgroundColor__ID , borderColor__ID, null, true, true, null, true, 'bottom') );",
        ];

        yield 'bar, scheme' => [
            'bar', ['colorScheme' => 'Blues'],
            "const backgroundColor__ID = d3.schemeBlues[9]; const borderColor__ID = 'blues';",
        ];

        yield 'bar, switched off' => [
            'bar',
            ['showTitle' => false, 'showLegend' => false, 'enableAnimation' => false],
            "'bar', null, backgroundColor__ID , borderColor__ID, null, null, null, null, null, 'bottom')",
        ];

        yield 'doughnut, scheme' => [
            'doughnut', ['colorScheme' => 'Greens', 'legendPosition' => 'left'],
            "const backgroundColor__ID = d3.schemeGreens[9]; const borderColor__ID = 'Greens';",
        ];

        yield 'doughnut, legend position' => [
            'doughnut', ['legendPosition' => 'left'],
            "'doughnut', null, backgroundColor__ID , borderColor__ID, null, true, true, null, true, 'left')",
        ];

        yield 'pie, scheme' => [
            'pie', ['colorScheme' => 'Reds', 'showTitle' => false],
            "const backgroundColor__ID = d3.schemeReds[9]; const borderColor__ID = 'Reds';",
        ];

        yield 'pie, title off' => [
            'pie', ['showTitle' => false],
            "'pie', null, backgroundColor__ID , borderColor__ID, null, true, null, null, true, 'bottom')",
        ];

        yield 'polarArea, scheme' => [
            'polarArea', ['colorScheme' => 'Purples'],
            "const backgroundColor__ID = d3.schemePurples[9]; const borderColor__ID = 'Purples';",
        ];

        yield 'polarArea, legend off' => [
            'polarArea', ['showLegend' => false],
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
