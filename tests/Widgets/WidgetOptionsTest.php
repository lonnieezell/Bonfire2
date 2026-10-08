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

use Bonfire\Widgets\WidgetOptions;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;
use Config\App;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class WidgetOptionsTest extends TestCase
{
    protected $refresh = true;

    #[DataProvider('provideSets')]
    public function testFindsEachSetByItsTabAlias(string $alias)
    {
        $this->assertSame($alias, WidgetOptions::forAlias($alias)->alias());
    }

    public function testUnknownAliasOrChartTypeHasNoOptions()
    {
        $this->assertNotInstanceOf(WidgetOptions::class, WidgetOptions::forAlias('radarchart'));
        $this->assertNotInstanceOf(WidgetOptions::class, WidgetOptions::forChartType('radar'));
    }

    public function testNamesKeysAfterTheConfigClassAndTypePrefix()
    {
        $this->assertSame('Stats.stats_showLink', WidgetOptions::forAlias('stats')->key('showLink'));
        $this->assertSame('LineChart.line_tension', WidgetOptions::forChartType('line')->key('tension'));
        $this->assertSame('LineChart.useCustomSettings', WidgetOptions::forChartType('line')->key('useCustomSettings'));
        $this->assertSame('PolarAreaChart.polarArea_colorScheme', WidgetOptions::forChartType('polarArea')->key('colorScheme'));
    }

    public function testReadsStoredValuesAndDefaultsFromConfig()
    {
        $bar = WidgetOptions::forChartType('bar');

        $this->assertSame('bottom', $bar->get('legendPosition'));

        setting('BarChart.bar_legendPosition', 'top');
        $this->assertSame('top', $bar->get('legendPosition'));
    }

    public function testOptionsAChartTypeDoesNotHaveReadAsNull()
    {
        $this->assertNull(WidgetOptions::forChartType('bar')->get('showSubTitle'));
        $this->assertNull(WidgetOptions::forChartType('bar')->get('tension'));
    }

    #[DataProvider('provideSets')]
    public function testSaveStoresPostedValuesAndFallsBackWhenAbsent(string $alias)
    {
        $options = WidgetOptions::forAlias($alias);
        $first   = $options->optionNames()[0];

        $options->save($this->form([$options->fieldName($first) => 'on']));
        $this->assertSame('on', setting($options->key($first)));

        $options->save($this->form([]));
        $this->assertEmpty(setting($options->key($first)));
    }

    #[DataProvider('provideSets')]
    public function testResetReturnsEveryOptionToItsDefault(string $alias)
    {
        $options = WidgetOptions::forAlias($alias);
        $options->reset();
        $before = array_map($options->get(...), $options->optionNames());

        $options->save($this->form(array_fill_keys(array_map($options->fieldName(...), $options->optionNames()), 'x')));
        $this->assertNotSame($before, array_map($options->get(...), $options->optionNames()));

        $options->reset();

        $this->assertSame($before, array_map($options->get(...), $options->optionNames()));
    }

    public static function provideSets(): iterable
    {
        foreach (['stats', 'linechart', 'barchart', 'doughnutchart', 'piechart', 'polarareachart'] as $alias) {
            yield $alias => [$alias];
        }
    }

    public function testCustomLineSettingsAreOnlyKeptWhileTheirSwitchIsOn()
    {
        $line = WidgetOptions::forChartType('line');

        $line->save($this->form(['useCustomSettings' => 'on', 'line_borderColor' => '#ff0000']));
        $this->assertSame('#ff0000', $line->get('borderColor'));
        $this->assertSame(1, $line->get('borderWidth'));

        $line->save($this->form(['useCustomSettings' => 'on']));
        $this->assertSame('#000000', $line->get('borderColor'));

        $line->save($this->form(['line_borderColor' => '#00ff00']));
        $this->assertNull($line->get('borderColor'));
        $this->assertNull($line->get('borderWidth'));
    }

    public function testAllReturnsEverySet()
    {
        $this->assertSame(
            ['stats', 'linechart', 'barchart', 'doughnutchart', 'piechart', 'polarareachart'],
            array_map(static fn (WidgetOptions $o) => $o->alias(), WidgetOptions::all()),
        );
    }

    /**
     * @param array<string, string> $post
     */
    private function form(array $post): IncomingRequest
    {
        $request = new IncomingRequest(new App(), new URI('http://example.com/'), null, new UserAgent());
        $request->setGlobal('post', $post);

        return $request;
    }
}
