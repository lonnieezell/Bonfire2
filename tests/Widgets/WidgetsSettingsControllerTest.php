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

use CodeIgniter\Shield\Entities\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\SubmitsForms;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class WidgetsSettingsControllerTest extends TestCase
{
    use SubmitsForms;

    protected $refresh = true;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser();
        $this->user->addGroup('superadmin');
    }

    public function testIndexListsEveryWidgetWithItsEnabledState()
    {
        setting('Stats.Stats_userGroups643', false);

        $response = $this->actingAs($this->user)->get('/admin/settings/widgets');

        $response->assertOK();
        $response->assertSee('widget "USERS IN RECYCLER"');
        $response->assertSee('Enable Chart line widget');
        $response->assertSee('Enable Log widget "Log activity"');
        $this->assertStringContainsString('id="Stats_usersInRecycler693" checked>', $response->getBody());
        $this->assertStringContainsString('id="Stats_userGroups643">', $response->getBody());
    }

    public function testResettingTheSiteDefaultForgetsTheStoredLayout()
    {
        $configured = config('Dashboard')->layout;
        setting('Dashboard.layout', ['version' => 1, 'sections' => []]);

        $this->actingAs($this->user)->post(route_to('widgets-reset-layout'), [csrf_token() => csrf_hash()])->assertRedirect();

        $this->assertSame($configured, setting('Dashboard.layout'));
    }

    public function testOnlyWidgetsSettingsHoldersMayResetTheSiteDefault()
    {
        $user = $this->createUser();
        $user->addPermission('admin.access');
        $stored = ['version' => 1, 'sections' => []];
        setting('Dashboard.layout', $stored);

        $this->actingAs($user)->post(route_to('widgets-reset-layout'), [csrf_token() => csrf_hash()])->assertStatus(403);

        $this->assertSame($stored, setting('Dashboard.layout'));
    }

    public function testTheSettingsPageOffersToResetTheSiteDefaultWithAConfirmation()
    {
        $response = $this->actingAs($this->user)->get('/admin/settings/widgets');

        $this->assertMatchesRegularExpression('#<form action="' . preg_quote(url_to('widgets-reset-layout'), '#') . '" method="post">.*?<button type="submit"[^>]*data-confirm=#s', $response->getBody());
    }

    public function testSavingEnabledFlagsStoresOnAndForgetsTheRest()
    {
        setting('Stats.Stats_usersInRecycler693', 'on');

        $this->actingAs($this->user)
            ->post('/admin/settings/widgets', ['Stats_userGroups643' => 'on'])
            ->assertRedirect();

        $this->assertSame('on', setting('Stats.Stats_userGroups643'));
        $this->assertEmpty(setting('Stats.Stats_usersInRecycler693'));
    }

    public function testSavingStatsOptions()
    {
        $this->actingAs($this->user)
            ->post('/admin/settings/widgets', ['widget' => 'stats', 'stats_showLink' => 'on']);
        $this->assertSame('on', setting('Stats.stats_showLink'));

        $this->actingAs($this->user)
            ->post('/admin/settings/widgets', ['widget' => 'stats']);
        $this->assertEmpty(setting('Stats.stats_showLink'));
    }

    public function testSavingLineChartOptions()
    {
        $this->actingAs($this->user)->post('/admin/settings/widgets', [
            'widget'              => 'linechart',
            'line_showTitle'      => 'on',
            'line_legendPosition' => 'top',
            'line_tension'        => '0.5',
            'useCustomSettings'   => 'on',
            'line_borderColor'    => '#ff0000',
            'line_borderWidth'    => '3',
        ]);

        $this->assertSame('on', setting('LineChart.line_showTitle'));
        $this->assertEmpty(setting('LineChart.line_showLegend'));
        $this->assertSame('top', setting('LineChart.line_legendPosition'));
        $this->assertSame('0.5', setting('LineChart.line_tension'));
        $this->assertSame('on', setting('LineChart.useCustomSettings'));
        $this->assertSame('#ff0000', setting('LineChart.line_borderColor'));
        $this->assertSame('3', setting('LineChart.line_borderWidth'));
    }

    public function testSavingLineChartWithoutCustomSettingsForgetsTheCustomOnes()
    {
        setting('LineChart.line_borderColor', '#ff0000');
        setting('LineChart.line_borderWidth', '3');

        $this->actingAs($this->user)->post('/admin/settings/widgets', [
            'widget'           => 'linechart',
            'line_borderColor' => '#00ff00',
        ]);

        $this->assertEmpty(setting('LineChart.useCustomSettings'));
        $this->assertNotSame('#ff0000', setting('LineChart.line_borderColor'));
        $this->assertNotSame('#00ff00', setting('LineChart.line_borderColor'));
        $this->assertNotSame('3', setting('LineChart.line_borderWidth'));
    }

    #[DataProvider('provideSavingSchemedChartOptions')]
    public function testSavingSchemedChartOptions(string $alias, string $class, string $prefix)
    {
        $this->actingAs($this->user)->post('/admin/settings/widgets', [
            'widget'                    => $alias,
            $prefix . '_showLegend'     => 'on',
            $prefix . '_legendPosition' => 'left',
            $prefix . '_colorScheme'    => 'Blues',
        ]);

        $this->assertSame('on', setting("{$class}.{$prefix}_showLegend"));
        $this->assertEmpty(setting("{$class}.{$prefix}_showTitle"));
        $this->assertEmpty(setting("{$class}.{$prefix}_enableAnimation"));
        $this->assertSame('left', setting("{$class}.{$prefix}_legendPosition"));
        $this->assertSame('Blues', setting("{$class}.{$prefix}_colorScheme"));

        $this->actingAs($this->user)->post('/admin/settings/widgets', ['widget' => $alias]);

        $this->assertSame('null', setting("{$class}.{$prefix}_colorScheme"));
    }

    public static function provideSavingSchemedChartOptions(): iterable
    {
        yield 'bar' => ['barchart', 'BarChart', 'bar'];

        yield 'doughnut' => ['doughnutchart', 'DoughnutChart', 'doughnut'];

        yield 'pie' => ['piechart', 'PieChart', 'pie'];

        yield 'polarArea' => ['polarareachart', 'PolarAreaChart', 'polarArea'];
    }

    public function testResetForgetsEverything()
    {
        setting('Stats.Stats_usersInRecycler693', 'on');
        setting('Stats.stats_showLink', false);
        setting('LineChart.line_tension', '0.9');
        setting('LineChart.useCustomSettings', 'on');
        setting('LineChart.line_borderColor', '#ff0000');
        setting('BarChart.bar_colorScheme', 'Blues');
        setting('DoughnutChart.doughnut_legendPosition', 'left');
        setting('PieChart.pie_showTitle', false);
        setting('PolarAreaChart.polarArea_enableAnimation', false);
        setting('Stats.Logs_logActivity', 'on');
        setting('LogActivity.logActivity_fileCount', '5');
        setting('LogActivity.logActivity_hideDebug', 'on');

        $this->actingAs($this->user)
            ->post('/admin/settings/widgetsReset')
            ->assertRedirect();

        $this->assertEmpty(setting('Stats.Stats_usersInRecycler693'));
        $this->assertTrue(setting('Stats.stats_showLink'));
        $this->assertEqualsWithDelta(0.1, setting('LineChart.line_tension'), PHP_FLOAT_EPSILON);
        $this->assertEmpty(setting('LineChart.useCustomSettings'));
        $this->assertEmpty(setting('LineChart.line_borderColor'));
        $this->assertSame('null', setting('BarChart.bar_colorScheme'));
        $this->assertSame('bottom', setting('DoughnutChart.doughnut_legendPosition'));
        $this->assertTrue(setting('PieChart.pie_showTitle'));
        $this->assertTrue(setting('PolarAreaChart.polarArea_enableAnimation'));
        $this->assertEmpty(setting('Stats.Logs_logActivity'));
        $this->assertSame(7, setting('LogActivity.logActivity_fileCount'));
        $this->assertFalse(setting('LogActivity.logActivity_hideDebug'));
    }

    public function testAdminWithoutPermissionIsRedirected()
    {
        $user = $this->createUser();
        $user->addGroup('user');

        $this->actingAs($user)
            ->post('/admin/settings/widgets', ['widget' => 'stats', 'stats_showLink' => 'on']);

        $this->assertTrue(setting('Stats.stats_showLink'));
        $this->assertNotSame('on', setting('Stats.stats_showLink'));
    }

    public function testEnabledFlagsRoundTripThroughTheForm(): void
    {
        setting('Stats.Stats_usersInRecycler693', 'on');
        setting('Stats.Stats_userGroups643', false);

        $this->submitTheForm('/admin/settings/widgets');

        $this->assertSame('on', setting('Stats.Stats_usersInRecycler693'));
        $this->assertEmpty(setting('Stats.Stats_userGroups643'));
    }

    /**
     * @param array<string, mixed> $settings
     */
    #[DataProvider('provideOptionsRoundTripThroughTheForm')]
    public function testOptionsRoundTripThroughTheForm(string $alias, array $settings): void
    {
        foreach ($settings as $key => $value) {
            setting($key, $value);
        }

        $this->submitTheForm('/admin/settings/widgets/' . $alias);

        foreach ($settings as $key => $value) {
            $this->assertSame($value, setting($key), $key);
        }
    }

    public static function provideOptionsRoundTripThroughTheForm(): iterable
    {
        yield 'stats on' => ['stats', ['Stats.stats_showLink' => 'on']];

        yield 'stats off' => ['stats', ['Stats.stats_showLink' => false]];

        yield 'line' => ['linechart', [
            'LineChart.line_showTitle'       => 'on',
            'LineChart.line_showLegend'      => false,
            'LineChart.line_legendPosition'  => 'right',
            'LineChart.line_enableAnimation' => 'on',
            'LineChart.line_tension'         => '0.4',
        ]];

        yield 'log activity' => ['logactivity', [
            'LogActivity.logActivity_hideDebug' => 'on',
            'LogActivity.logActivity_fileCount' => '14',
        ]];

        yield 'log activity, debug shown' => ['logactivity', [
            'LogActivity.logActivity_hideDebug' => false,
            'LogActivity.logActivity_fileCount' => '12',
        ]];

        foreach (self::provideSavingSchemedChartOptions() as $name => [$alias, $class, $prefix]) {
            yield $name => [$alias, [
                "{$class}.{$prefix}_showTitle"       => false,
                "{$class}.{$prefix}_showLegend"      => 'on',
                "{$class}.{$prefix}_legendPosition"  => 'left',
                "{$class}.{$prefix}_enableAnimation" => 'on',
                "{$class}.{$prefix}_colorScheme"     => 'Greens',
            ]];
        }
    }

    private function submitTheForm(string $path): void
    {
        $html   = $this->actingAs($this->user)->get($path)->getBody();
        $fields = $this->formFields($html, site_url(ADMIN_AREA . '/settings/widgets'));

        $this->actingAs($this->user)->post('/admin/settings/widgets', $fields)->assertRedirect();
    }
}
