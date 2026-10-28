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

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Shield\Entities\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\RendersDashboard;
use Tests\Support\SubmitsForms;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class WidgetsSettingsControllerTest extends TestCase
{
    use RendersDashboard;
    use SubmitsForms;

    protected $refresh = true;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->forgetDashboardState();
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

    public function testResetForgetsEverything()
    {
        setting('Stats.Stats_usersInRecycler693', 'on');
        setting('Stats.stats_showLink', false);
        setting('LineChart.line_tension', 0.9);
        setting('BarChart.bar_colorScheme', 'Blues');
        setting('LogActivity.logActivity_fileCount', 5);

        $this->actingAs($this->user)
            ->post('/admin/settings/widgetsReset')
            ->assertRedirect();

        foreach (['Stats.Stats_usersInRecycler693', 'Stats.stats_showLink', 'LineChart.line_tension', 'BarChart.bar_colorScheme', 'LogActivity.logActivity_fileCount'] as $key) {
            $this->assertNull(setting($key), $key);
        }
    }

    public function testAdminWithoutPermissionIsRedirected()
    {
        $user = $this->createUser();
        $user->addGroup('user');

        $this->actingAs($user)
            ->post('/admin/settings/widgets', ['widget' => 'stats', 'showLink' => '']);

        $this->assertNull(setting('Stats.stats_showLink'));
    }

    public function testEnabledFlagsRoundTripThroughTheForm(): void
    {
        setting('Stats.Stats_usersInRecycler693', 'on');
        setting('Stats.Stats_userGroups643', false);

        $this->submitTheForm('/admin/settings/widgets');

        $this->assertSame('on', setting('Stats.Stats_usersInRecycler693'));
        $this->assertEmpty(setting('Stats.Stats_userGroups643'));
    }

    public function testValuesStoredByEarlierVersionsAreStillRead(): void
    {
        setting('LogActivity.logActivity_fileCount', '14');
        setting('LogActivity.logActivity_hideDebug', 'on');

        $shown = $this->shownFields('/admin/settings/widgets/logactivity');

        $this->assertSame('14', $shown['fileCount'] ?? null);
        $this->assertSame('1', $shown['hideDebug'] ?? null);
    }

    public function testAValueThatIsNotOneKeepsTheStoredDefault(): void
    {
        setting('BarChart.bar_colorScheme', 'Blues');

        $this->actingAs($this->user)->post('/admin/settings/widgets', ['widget' => 'barchart', 'colorScheme' => 'Bogus'])->assertRedirect();

        $this->assertSame('Blues', setting('BarChart.bar_colorScheme'));
    }

    public function testATabNoWidgetIsKeyedToIsNotFound(): void
    {
        $this->expectException(PageNotFoundException::class);

        $this->actingAs($this->user)->get('/admin/settings/widgets/radarchart');
    }

    public function testTheSchemePreviewShowsOnlyAKnownScheme(): void
    {
        // A GET, so the preview does not use up the form's CSRF token
        $preview = fn (string $scheme) => $this->actingAs($this->user)->get('/admin/settings/widgets/schemePreview?' . http_build_query(['colorScheme' => $scheme]))->getBody();

        $this->assertStringContainsString('color_scheme/Blues.png', $preview('Blues'));
        $this->assertStringNotContainsString('<img', $preview('null'));
        $this->assertStringNotContainsString('<img', $preview('"><script>'));
    }

    /**
     * @param string|null $posted What the admin enters; null switches a toggle off
     */
    #[DataProvider('provideAFieldRoundTripsThroughTheSiteDefaultAndThePlacement')]
    public function testAFieldRoundTripsThroughTheSiteDefaultAndThePlacement(string $tab, string $widget, string $field, ?string $posted, bool|float|int|string $inherited): void
    {
        $path   = '/admin/settings/widgets/' . $tab;
        $fields = $this->shownFields($path);
        $this->assertArrayHasKey('widget', $fields);

        if ($posted === null) {
            unset($fields[$field]);
        } else {
            $fields[$field] = $posted;
        }

        $this->actingAs($this->user)->post('/admin/settings/widgets', $fields)->assertRedirect();

        $shown = $this->shownFields($path);
        $this->assertSame($posted, $shown[$field] ?? null, 'The tab shows the stored default');

        preg_match('#<template data-settings-for="' . preg_quote(esc($widget, 'attr'), '#') . '" data-fields="([^"]+)"#', $this->dashboardHtml($this->user), $match);
        $placed = array_column(json_decode(html_entity_decode($match[1] ?? ''), true) ?? [], 'inherited', 'name');
        $this->assertSame($inherited, $placed[$field] ?? null, 'The placed widget inherits it');
    }

    public static function provideAFieldRoundTripsThroughTheSiteDefaultAndThePlacement(): iterable
    {
        yield 'stats link off' => ['stats', 'stats:usersInRecycler693', 'showLink', null, false];

        yield 'line tension' => ['linechart', 'charts:usersByGroupLine123', 'tension', '0.4', 0.4];

        yield 'bar legend position' => ['barchart', 'charts:usersByGroupBar345', 'legendPosition', 'left', 'left'];

        yield 'doughnut legend off' => ['doughnutchart', 'charts:usersByGroupDou654', 'showLegend', null, false];

        yield 'pie color scheme' => ['piechart', 'charts:usersByGroupPie223', 'colorScheme', 'Greens', 'Greens'];

        yield 'polar area animation off' => ['polarareachart', 'charts:usersByGroupPiePolar645', 'enableAnimation', null, false];

        yield 'log activity days' => ['logactivity', 'logs:logActivity', 'fileCount', '14', 14];

        yield 'log activity debug hidden' => ['logactivity', 'logs:logActivity', 'hideDebug', '1', true];
    }

    /**
     * What the form on that page would post.
     *
     * @return array<string, mixed>
     */
    private function shownFields(string $path): array
    {
        return $this->formFields($this->actingAs($this->user)->get($path)->getBody(), site_url(ADMIN_AREA . '/settings/widgets'));
    }

    private function submitTheForm(string $path): void
    {
        $fields = $this->shownFields($path);

        $this->actingAs($this->user)->post('/admin/settings/widgets', $fields)->assertRedirect();
    }
}
