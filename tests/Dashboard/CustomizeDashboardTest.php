<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Dashboard;

use Bonfire\Users\User;
use Bonfire\Widgets\Types\Charts\ChartsItem;
use Bonfire\Widgets\Types\Stats\StatsItem;
use Tests\Support\Libraries\StubDashboard;
use Tests\Support\RendersDashboard;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class CustomizeDashboardTest extends TestCase
{
    use RendersDashboard;

    private const QUICK_LINKS    = 'cell:Bonfire\Dashboard\DashboardCells::quickLinks';
    private const DEFAULT_LAYOUT = ['version' => 1, 'sections' => [
        ['title' => '', 'widgets' => [['id' => self::QUICK_LINKS, 'size' => 'full']]],
        ['title' => 'Users', 'widgets' => [['id' => 'stats:usersExpTable693', 'size' => 'half'], ['id' => 'stats:usersInRecycler693', 'size' => 'half']]],
        ['title' => 'Monitoring', 'widgets' => [['id' => 'logs:logActivity', 'size' => 'full']]],
    ]];
    private const DEFAULT_IDS = [self::QUICK_LINKS, 'stats:usersExpTable693', 'stats:usersInRecycler693', 'logs:logActivity'];

    protected $refresh = true;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->forgetDashboardState();
        $this->user = $this->admin();
    }

    protected function tearDown(): void
    {
        $this->forgetDashboardState();

        parent::tearDown();
    }

    public function testTheDashboardOffersTheEditorItsLayoutAndSaveUrl(): void
    {
        $html = $this->dashboardHtml($this->user);

        $this->assertMatchesRegularExpression('#<div class="bf-dashboard"[^>]*x-data="dashboardEditor"#', $html);
        $this->assertStringContainsString('data-save-url="' . url_to('dashboard-layout') . '"', $html);
        $this->assertMatchesRegularExpression('#<script[^>]+src="https://cdn\.jsdelivr\.net/npm/sortablejs@1\.15\.6/Sortable\.min\.js"[^>]+integrity="sha256-#', $html);
    }

    public function testSavingStoresTheOrderForThatUserOnly(): void
    {
        $other = $this->admin();

        $this->save($this->user, [['title' => '', 'widgets' => [
            ['id' => 'stats:usersInRecycler693', 'size' => 'quarter'],
            ['id' => self::QUICK_LINKS, 'size' => 'full'],
        ]]])->assertOK();

        $this->assertSame(['stats:usersInRecycler693', self::QUICK_LINKS], $this->placedIds($this->user));
        $this->assertSame(self::DEFAULT_IDS, $this->placedIds($other));
    }

    public function testSavingDropsUnknownUnavailableAndDuplicateIds(): void
    {
        setting('Stats.Stats_usersExpTable693', false);

        $this->save($this->user, [['title' => '', 'widgets' => [
            ['id' => 'stats:usersInRecycler693', 'size' => 'quarter'],
            ['id' => 'stats:nope', 'size' => 'quarter'],
            ['id' => 'stats:usersExpTable693', 'size' => 'quarter'],
            ['id' => 'stats:usersInRecycler693', 'size' => 'quarter'],
        ]]])->assertOK();

        $this->assertSame(['stats:usersInRecycler693'], $this->placedIds($this->user));
    }

    public function testAViewerLackingAWidgetsPermissionSeesItNeitherOnTheDashboardNorInThePalette(): void
    {
        $user = $this->createUser();
        $user->addPermission('admin.access');

        $html = $this->dashboardHtml($user);

        $this->assertSame([self::QUICK_LINKS], $this->placedIds($user));
        $this->assertArrayNotHasKey('stats:usersInRecycler693', $this->paletteEntries($html));
        $this->assertArrayNotHasKey('stats:userGroups643', $this->paletteEntries($html));
        $this->assertArrayHasKey(self::QUICK_LINKS, $this->paletteEntries($html));
    }

    public function testSavingDropsWidgetsTheUserMayNotSee(): void
    {
        $user = $this->createUser();
        $user->addPermission('admin.access', 'groups.settings');

        $this->save($user, [['title' => '', 'widgets' => [
            ['id' => 'stats:usersInRecycler693', 'size' => 'quarter'],
            ['id' => 'stats:userGroups643', 'size' => 'quarter'],
        ]]])->assertOK();

        $this->assertSame(['stats:userGroups643'], array_column(setting()->get('Dashboard.layout', 'user:' . $user->id)['sections'][0]['widgets'], 'id'));
    }

    public function testSectionsAndSizesPersistAndRender(): void
    {
        $this->save($this->user, [
            ['title' => 'People <b>', 'widgets' => [['id' => 'stats:usersInRecycler693', 'size' => 'two-thirds']]],
            ['title' => '', 'widgets' => [['id' => 'stats:userGroups643', 'size' => 'quarter']]],
        ])->assertOK();

        $html = $this->dashboardHtml($this->user);

        $this->assertMatchesRegularExpression('#<section class="bf-dashboard-section"[^>]*>\s*<h2 class="bf-dashboard-section-title"[^>]*>People &lt;b&gt;</h2>#', $html);
        $this->assertSame(1, substr_count($html, 'class="bf-dashboard-section-title"'));
        $this->assertMatchesRegularExpression('#class="bf-widget bf-span-8" data-widget-id="stats&\#x3A;usersInRecycler693"#', $html);
    }

    public function testTooManySectionsAreRefused(): void
    {
        $this->save($this->user, array_fill(0, 21, ['title' => '', 'widgets' => [['id' => 'stats:usersInRecycler693']]]))
            ->assertStatus(400);
    }

    public function testTheSizeToolbarOffersTheWidgetsPresets(): void
    {
        setting('Stats.Logs_logActivity', 'on');
        $this->save($this->user, [['title' => '', 'widgets' => [
            ['id' => 'stats:usersInRecycler693', 'size' => 'quarter'],
            ['id' => 'logs:logActivity', 'size' => 'full'],
        ]]]);
        $html = $this->dashboardHtml($this->user);

        $this->assertSame(['quarter', 'third', 'half', 'two-thirds', 'full'], $this->sizeOptions($html, 'stats&#x3A;usersInRecycler693'));
        $this->assertSame(['half', 'two-thirds', 'full'], $this->sizeOptions($html, 'logs&#x3A;logActivity'));
    }

    public function testTheEditorKnowsEachSizesColumnsAndTheDefaultLayout(): void
    {
        $html = $this->dashboardHtml($this->user);

        preg_match('#data-size-columns="([^"]+)"#', $html, $columns);
        preg_match('#data-default-layout="([^"]+)"#', $html, $default);

        $this->assertSame(['quarter' => 3, 'third' => 4, 'half' => 6, 'two-thirds' => 8, 'full' => 12], json_decode(html_entity_decode($columns[1] ?? ''), true));
        $this->assertSame(self::DEFAULT_LAYOUT, json_decode(html_entity_decode($default[1] ?? ''), true));
    }

    public function testAFreshDashboardShowsTheDefaultLayoutAndOffersEveryWidgetInThePalette(): void
    {
        setting()->flush();
        setting('Stats.Stats_usersExpTable693', false);

        $entries = $this->paletteEntries($this->dashboardHtml($this->user));

        $this->assertSame([self::QUICK_LINKS, 'stats:usersInRecycler693', 'logs:logActivity'], $this->placedIds($this->user));
        $this->assertTrue($entries[self::QUICK_LINKS]['placed']);
        $this->assertTrue($entries['stats:usersInRecycler693']['placed']);
        $this->assertTrue($entries['logs:logActivity']['placed']);
        $this->assertFalse($entries['stats:userGroups643']['placed']);
        $this->assertFalse($entries['charts:usersByGroupBar345']['placed']);
        $this->assertArrayNotHasKey('stats:usersExpTable693', $entries);
    }

    public function testAnInstallListingTheOldQuickLinksCellRendersItOnce(): void
    {
        setting('Dashboard.cells', ['Bonfire\Dashboard\DashboardCells::quickLinks', 'Bonfire\Widgets\Cells\WidgetCells::stats']);

        $html = $this->dashboardHtml($this->user);

        $this->assertSame(self::DEFAULT_IDS, $this->placedIds($this->user));
        $this->assertSame(1, substr_count($this->placedHtml($html), 'class="bf-page-head-date"'));
        $this->assertSame(1, substr_count($html, 'data-palette-widget="' . esc(self::QUICK_LINKS, 'attr') . '"'));
    }

    public function testThePaletteListsAvailableWidgetsMarkingPlacedOnes(): void
    {
        setting('Stats.Stats_usersExpTable693', false);
        $this->save($this->user, [['title' => '', 'widgets' => [['id' => 'stats:usersInRecycler693', 'size' => 'quarter']]]]);

        $entries = $this->paletteEntries($this->dashboardHtml($this->user));

        $this->assertTrue($entries['stats:usersInRecycler693']['placed']);
        $this->assertFalse($entries['stats:userGroups643']['placed']);
        $this->assertFalse($entries[self::QUICK_LINKS]['placed']);
        $this->assertArrayNotHasKey('stats:usersExpTable693', $entries);
        $this->assertSame('stats', $entries['stats:userGroups643']['group']);
        $this->assertSame('quickLinks', $entries[self::QUICK_LINKS]['group']);
    }

    public function testEachPaletteEntryCarriesAPlaceholderTileAtItsDefaultSize(): void
    {
        $html = $this->dashboardHtml($this->user);

        $this->assertMatchesRegularExpression(
            '#data-palette-widget="stats&\#x3A;userGroups643".*?<template data-placeholder>\s*(?:<!--.*?-->\s*)*<div class="bf-widget bf-span-3" data-widget-id="stats&\#x3A;userGroups643" data-size="quarter"#s',
            $html,
        );
    }

    public function testTilesCanBeRemoved(): void
    {
        $this->assertMatchesRegularExpression('#data-widget-id="stats&\#x3A;usersInRecycler693".*?<button[^>]*@click="removeWidget\(\$el\)"#s', $this->dashboardHtml($this->user));
    }

    public function testAWidgetMadeAvailableLaterOnlyAppearsInACustomizedUsersPalette(): void
    {
        setting('Stats.Stats_usersExpTable693', false);
        $this->save($this->user, [['title' => '', 'widgets' => [['id' => 'stats:usersInRecycler693', 'size' => 'quarter']]]]);
        setting('Stats.Stats_usersExpTable693', 'on');

        $this->assertSame(['stats:usersInRecycler693'], $this->placedIds($this->user));
        $this->assertFalse($this->paletteEntries($this->dashboardHtml($this->user))['stats:usersExpTable693']['placed']);
    }

    public function testChangesAreAnnouncedThroughALiveRegion(): void
    {
        $html = $this->dashboardHtml($this->user);

        $this->assertMatchesRegularExpression('#<div[^>]*aria-live="polite"[^>]*x-text="announcement"#', $html);
        $this->assertMatchesRegularExpression('#data-messages="[^"]+"#', $html);
    }

    public function testTilesMoveAndResizeFromTheKeyboard(): void
    {
        $html = $this->dashboardHtml($this->user);

        preg_match('#<div class="bf-widget [^>]*data-widget-id="cell&\#x3A;[^>]*>#', $html, $tile);

        $this->assertMatchesRegularExpression('#data-title="[^"]+"#', $tile[0]);
        $this->assertMatchesRegularExpression('#@keydown="tileKey\(\$event\)"#', $tile[0]);
        $this->assertMatchesRegularExpression('#:aria-describedby="[^"]*bf-dashboard-help#', $tile[0]);
        $this->assertMatchesRegularExpression('#id="bf-dashboard-help"#', $html);
    }

    public function testSectionsMoveUpAndDownWithoutDragging(): void
    {
        $html = $this->dashboardHtml($this->user);

        $this->assertMatchesRegularExpression('#<button[^>]*@click="moveSection\(\$el, -1\)"[^>]*:disabled=#', $html);
        $this->assertMatchesRegularExpression('#<button[^>]*@click="moveSection\(\$el, 1\)"[^>]*:disabled=#', $html);
    }

    public function testOnlyWidgetsWithSettingsOfferThem(): void
    {
        $html = $this->dashboardHtml($this->user);

        $this->assertStringContainsString('data-widget-settings', $this->tileTools($html, 'logs&#x3A;logActivity'));
        $this->assertStringNotContainsString('data-widget-settings', $this->tileTools($html, 'cell&#x3A;Bonfire&#x5C;Dashboard&#x5C;DashboardCells&#x3A;&#x3A;quickLinks'));
    }

    public function testEachWidgetWithSettingsHasAFormForThem(): void
    {
        $html = $this->dashboardHtml($this->user);

        $form = $this->settingsForm($html, 'logs&#x3A;logActivity');
        $this->assertMatchesRegularExpression('#<input[^>]*name="fileCount"#', $form);
        $this->assertMatchesRegularExpression('#<input[^>]*name="hideDebug"#', $form);
        $this->assertSame('', $this->settingsForm($html, 'cell&#x3A;Bonfire&#x5C;Dashboard&#x5C;DashboardCells&#x3A;&#x3A;quickLinks'));
    }

    public function testAFormKnowsWhatEachFieldInherits(): void
    {
        setting('LogActivity.logActivity_fileCount', 10);

        preg_match('#<template data-settings-for="logs&\#x3A;logActivity" data-fields="([^"]+)"#', $this->dashboardHtml($this->user), $match);
        $fields = array_column(json_decode(html_entity_decode($match[1] ?? ''), true) ?? [], null, 'name');

        $this->assertSame(10, $fields['fileCount']['inherited'] ?? null);
        $this->assertFalse($fields['hideDebug']['inherited'] ?? null);
    }

    public function testAWidgetRendersWithTheSettingsSavedForIt(): void
    {
        $this->save($this->user, [['title' => '', 'widgets' => [
            ['id' => 'logs:logActivity', 'size' => 'full', 'settings' => ['fileCount' => 3]],
        ]]])->assertOK();

        $html = $this->placedHtml($this->dashboardHtml($this->user));

        $this->assertSame(3, substr_count($html, 'class="bf-log-label"'));
        $this->assertStringContainsString('data-settings="' . esc(json_encode(['fileCount' => 3]), 'attr') . '"', $html);
    }

    public function testAStatCardCanHideItsLink(): void
    {
        $this->save($this->user, [['title' => '', 'widgets' => [
            ['id' => 'stats:usersInRecycler693', 'size' => 'quarter', 'settings' => ['showLink' => false]],
            ['id' => 'stats:userGroups643', 'size' => 'quarter'],
        ]]])->assertOK();

        $html = $this->placedHtml($this->dashboardHtml($this->user));

        $this->assertSame(1, substr_count($html, 'class="bf-stat-link"'));
        $this->assertStringContainsString('data-widget-settings', $this->tileTools($html, 'stats&#x3A;usersInRecycler693'));
    }

    public function testTheSiteDefaultKeepsTheSettingsOfItsWidgets(): void
    {
        $this->save($this->user, [['title' => '', 'widgets' => [
            ['id' => 'logs:logActivity', 'size' => 'full', 'settings' => ['hideDebug' => true]],
        ]]], route: 'dashboard-site-layout')->assertOK();

        $this->assertSame(['hideDebug' => true], setting('Dashboard.layout')['sections'][0]['widgets'][0]['settings'] ?? null);
    }

    public function testThePaletteAddsToAChosenSection(): void
    {
        $this->assertMatchesRegularExpression(
            '#<select[^>]*x-model\.number="target"[^>]*>\s*<template x-for="\(name, index\) in sectionNames"#',
            $this->dashboardHtml($this->user),
        );
    }

    public function testAMalformedPayloadOrWrongVersionIsRejected(): void
    {
        $rejected = $this->save($this->user, 'nonsense');
        $rejected->assertStatus(400);
        $rejected->assertJSONFragment(['csrf' => csrf_hash()]);
        $this->save($this->user, [], 2)->assertStatus(400);

        $this->dontSeeInDatabase('settings', ['key' => 'layout', 'context' => 'user:' . $this->user->id]);
    }

    public function testAStoredLayoutSkipsWidgetsThatAreGone(): void
    {
        setting()->set('Dashboard.layout', ['version' => 1, 'sections' => [['title' => '', 'widgets' => [
            ['id' => 'stats:removedModule', 'size' => 'quarter'],
            ['id' => 'stats:userGroups643', 'size' => 'quarter'],
            ['id' => 'stats:usersInRecycler693', 'size' => 'quarter'],
        ]]]], 'user:' . $this->user->id);
        setting('Stats.Stats_userGroups643', false);

        $this->assertSame(['stats:usersInRecycler693'], $this->placedIds($this->user));
    }

    public function testSavingTheDefaultLayoutForgetsTheUsersOwn(): void
    {
        $this->save($this->user, [['title' => '', 'widgets' => [['id' => 'stats:usersInRecycler693', 'size' => 'quarter']]]]);

        $this->save($this->user, self::DEFAULT_LAYOUT['sections'])->assertOK();

        $this->dontSeeInDatabase('settings', ['key' => 'layout', 'context' => 'user:' . $this->user->id]);
        $this->assertSame(self::DEFAULT_IDS, $this->placedIds($this->user));
    }

    public function testSavingTheSiteDefaultChangesTheDashboardOfUsersWhoDidNotCustomize(): void
    {
        $customized = $this->admin();
        $fresh      = $this->admin();
        $this->save($customized, [['title' => '', 'widgets' => [['id' => 'stats:userGroups643', 'size' => 'quarter']]]]);

        $this->save($this->user, [['title' => '', 'widgets' => [['id' => 'stats:usersInRecycler693', 'size' => 'quarter']]]], route: 'dashboard-site-layout')
            ->assertOK();

        $this->assertSame(['stats:usersInRecycler693'], $this->placedIds($fresh));
        $this->assertSame(['stats:userGroups643'], $this->placedIds($customized));
    }

    public function testSavingTheSiteDefaultMakesTheSaverFollowIt(): void
    {
        $this->save($this->user, [['title' => '', 'widgets' => [['id' => 'stats:userGroups643', 'size' => 'quarter']]]]);

        $this->save($this->user, [['title' => '', 'widgets' => [['id' => 'stats:usersInRecycler693', 'size' => 'quarter']]]], route: 'dashboard-site-layout')
            ->assertOK();

        $this->assertSame(['stats:usersInRecycler693'], $this->placedIds($this->user));
    }

    public function testOnlyWidgetsSettingsHoldersMaySaveTheSiteDefault(): void
    {
        $user = $this->createUser();
        $user->addPermission('admin.access');

        $this->save($user, [['title' => '', 'widgets' => [['id' => 'stats:userGroups643', 'size' => 'quarter']]]], route: 'dashboard-site-layout')
            ->assertStatus(403);

        $this->assertSame(self::DEFAULT_LAYOUT, setting('Dashboard.layout'));
    }

    public function testOnlyWidgetsSettingsHoldersAreOfferedSaveAsSiteDefault(): void
    {
        $user = $this->createUser();
        $user->addPermission('admin.access');

        $this->assertStringContainsString('data-site-save-url="' . url_to('dashboard-site-layout') . '"', $this->dashboardHtml($this->user));
        $this->assertStringNotContainsString('data-site-save-url', $this->dashboardHtml($user));
    }

    public function testLazyQueriesRunOnlyForWidgetsInTheUsersLayout(): void
    {
        StubDashboard::inject(true);
        setting('Stats.Stats_users1', 'on');
        setting('Stats.Stats_users2', 'on');
        setting()->set('Dashboard.layout', ['version' => 1, 'sections' => [['title' => '', 'widgets' => [
            ['id' => 'stats:users1', 'size' => 'quarter'],
        ]]]], 'user:' . $this->user->id);
        $this->actingAs($this->user);

        $inLayout  = (new StatsItem())->setId('users1')->addValue('users');
        $offLayout = (new StatsItem())->setId('users2')->addValue('users');

        $this->assertNotNull($inLayout->value());
        $this->assertNull($offLayout->value());
    }

    public function testLazyQueriesDoNotRunForAViewerLackingTheItemsPermission(): void
    {
        StubDashboard::inject(true);
        setting('Stats.Stats_users1', 'on');
        $viewer = $this->createUser();
        $this->placeOnDashboard($viewer, 'stats:users1');
        $this->actingAs($viewer);

        $this->assertNull((new StatsItem(['id' => 'users1', 'permission' => 'users.list']))->addValue('users')->value());
        $this->assertNull((new ChartsItem(['id' => 'users1', 'permission' => 'users.list']))->addDataset('users', 'active', 'id')->data());

        $viewer->addPermission('users.list');

        $this->assertNotNull((new StatsItem(['id' => 'users1', 'permission' => 'users.list']))->addValue('users')->value());
    }

    /**
     * @return array<string, array{group: string, placed: bool}>
     */
    private function paletteEntries(string $html): array
    {
        $entries = [];

        foreach (array_slice(explode('<div class="bf-palette-group" data-palette-group="', $html), 1) as $chunk) {
            $group = strstr($chunk, '"', true);

            preg_match_all('#<li class="bf-palette-entry[^"]*" data-palette-widget="([^"]+)"( data-placed)?#', $chunk, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $entries[html_entity_decode($match[1])] = ['group' => $group, 'placed' => isset($match[2])];
            }
        }

        return $entries;
    }

    /**
     * The tool bar of the placed tile with that (escaped) id, before its content.
     */
    private function tileTools(string $html, string $id): string
    {
        preg_match('#<div class="bf-widget [^"]*" data-widget-id="' . preg_quote($id, '#') . '".*?<div class="bf-widget-body"#s', $html, $tile);

        return $tile[0] ?? '';
    }

    /**
     * @return list<string>
     */
    private function sizeOptions(string $html, string $id): array
    {
        preg_match_all('/data-size-option="([\w-]+)"/', $this->tileTools($html, $id), $options);

        return $options[1];
    }

    /**
     * The settings form template of the widget with that (escaped) id, or nothing.
     */
    private function settingsForm(string $html, string $id): string
    {
        preg_match('#<template data-settings-for="' . preg_quote($id, '#') . '".*?</template>#s', $html, $form);

        return $form[0] ?? '';
    }

    private function admin(): User
    {
        $user = $this->createUser();
        $user->addGroup('superadmin');

        return $user;
    }

    /**
     * @param list<array<string, mixed>>|string $sections
     */
    private function save(User $user, array|string $sections, int $version = 1, string $route = 'dashboard-layout')
    {
        $body = is_string($sections) ? $sections : json_encode(['version' => $version, 'sections' => $sections]);

        return $this->actingAs($user)
            ->withHeaders([config('Security')->headerName => csrf_hash(), 'Content-Type' => 'application/json'])
            ->withBody($body)
            ->post(route_to($route));
    }
}
