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

use Bonfire\Bonfire;
use Bonfire\Dashboard\Size;
use Bonfire\Dashboard\Widgets\QuickLinksWidget;
use Bonfire\Tools\Widgets\LogActivity;
use Config\Services;
use Tests\Support\Libraries\TestWidget;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class WidgetRegistryTest extends TestCase
{
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();

        setting()->flush();
        Services::resetSingle('widgets');
        Services::resetSingle('dashboard');
        (new Bonfire())->boot(true);
    }

    protected function tearDown(): void
    {
        // Booting the admin filled these shared services for this test only
        foreach (['menus', 'widgets', 'dashboard'] as $service) {
            Services::resetSingle($service);
        }

        setting()->flush();

        parent::tearDown();
    }

    public function testARegisteredWidgetIsFoundById(): void
    {
        $widget = new TestWidget('test:hello');

        service('dashboard')->register($widget);

        $this->assertSame($widget, service('dashboard')->widget('test:hello'));
        $this->assertArrayHasKey('test:hello', service('dashboard')->available());
    }

    public function testAnUnknownIdIsNotFound(): void
    {
        $this->assertNull(service('dashboard')->widget('test:missing'));
    }

    public function testStatsItemsBecomeWidgetsWithNamespacedIds(): void
    {
        $widget = service('dashboard')->widget('stats:usersInRecycler693');

        $this->assertNotNull($widget);
        $this->assertSame(lang('Users.usersModTitle'), $widget->group());
        $this->assertSame('fa fa-users', $widget->icon());
        $this->assertSame(Size::Quarter, $widget->sizes()->default);
    }

    public function testTheBuiltInUsersAndGroupsWidgetsDeclarePermissions(): void
    {
        $this->assertSame('users.list', service('dashboard')->widget('stats:usersInRecycler693')->permission());
        $this->assertSame('users.list', service('dashboard')->widget('users:byGroup')->permission());
        $this->assertSame('users.view', service('dashboard')->widget('users:failedLogins')->permission());
        $this->assertSame('users.list', service('dashboard')->widget('users:recentlyActive')->permission());
        $this->assertNull(service('dashboard')->widget('actions:runs')->permission());
        $this->assertSame('admin.settings', service('dashboard')->widget('tools:systemHealth')->permission());
        $this->assertSame('recycler.view', service('dashboard')->widget('recycler:summary')->permission());
        $this->assertSame('groups.settings', service('dashboard')->widget('stats:userGroups643')->permission());
        $this->assertNull(service('dashboard')->widget('cell:Bonfire\Dashboard\DashboardCells::quickLinks')->permission());
    }

    public function testChartsItemsTakeTheirDefaultSizeFromTheirCssClass(): void
    {
        $this->bootWithTestCharts();
        $widget = service('dashboard')->widget('charts:testBar');

        $this->assertNotNull($widget);
        $this->assertSame('Test charts', $widget->group());
        $this->assertSame(Size::Half, $widget->sizes()->default);
    }

    public function testLogActivityIsANativeWidgetUnderItsStoredId(): void
    {
        $widget = service('dashboard')->widget('logs:logActivity');

        $this->assertInstanceOf(LogActivity::class, $widget);
        $this->assertSame([], service('widgets')->widget('logs')->items());
        $this->assertSame(lang('Bonfire.toolsModTitle'), $widget->group());
        $this->assertSame(Size::Full, $widget->sizes()->default);
    }

    public function testQuickLinksIsANativeWidgetUnderItsCellId(): void
    {
        setting('Dashboard.cells', []);

        $widget = service('dashboard')->widget('cell:Bonfire\Dashboard\DashboardCells::quickLinks');

        $this->assertInstanceOf(QuickLinksWidget::class, $widget);
        $this->assertSame(lang('Bonfire.dashboard'), $widget->group());
    }

    public function testTheLibraryListsTheBuiltInWidgetsByModuleInNameOrder(): void
    {
        setting('Dashboard.cells', []);
        $user = $this->createUser();
        $user->addGroup('superadmin');
        auth()->login($user);

        $ids = array_map(static fn (array $widgets) => array_map(static fn ($widget) => $widget->id(), $widgets), service('dashboard')->grouped());

        $this->assertSame([lang('Actions.actionsModTitle'), lang('Bonfire.dashboard'), lang('Bonfire.toolsModTitle'), lang('Groups.groupsModTitle'), lang('Users.usersModTitle')], array_keys($ids));
        $this->assertSame(['actions:runs'], $ids[lang('Actions.actionsModTitle')]);
        $this->assertSame(['cell:Bonfire\Dashboard\DashboardCells::quickLinks'], $ids[lang('Bonfire.dashboard')]);
        $this->assertEqualsCanonicalizing(['tools:recentErrors', 'tools:systemHealth', 'logs:logActivity', 'recycler:summary'], $ids[lang('Bonfire.toolsModTitle')]);
        $this->assertSame(['stats:userGroups643'], $ids[lang('Groups.groupsModTitle')]);
        $this->assertEqualsCanonicalizing(['users:byGroup', 'users:newest', 'users:failedLogins', 'users:recentlyActive', 'stats:usersInRecycler693'], $ids[lang('Users.usersModTitle')]);
    }

    public function testAWidgetGroupedByTypeIsListedUnderThatTypesHeading(): void
    {
        service('dashboard')->register(new TestWidget('test:hello'));

        $this->assertSame('test:hello', service('dashboard')->grouped()[lang('Bonfire.groupCustom')][0]->id());
    }

    public function testCustomCellsBecomeWidgets(): void
    {
        setting('Dashboard.cells', ['App\Cells\Weather::forecast']);

        $widget = service('dashboard')->widget('cell:App\Cells\Weather::forecast');

        $this->assertNotNull($widget);
        $this->assertSame('custom', $widget->group());
        $this->assertSame(Size::Full, $widget->sizes()->default);
    }

    public function testAdaptersDescribeTheirWidgets(): void
    {
        $this->bootWithTestCharts();

        foreach (['stats:usersInRecycler693', 'charts:testBar', 'logs:logActivity', 'cell:Bonfire\Dashboard\DashboardCells::quickLinks'] as $id) {
            $this->assertNotSame('', service('dashboard')->widget($id)->description(), $id);
        }
    }

    public function testEachChartTypeOffersItsOwnSettings(): void
    {
        $this->bootWithTestCharts();
        $names = static fn (string $id) => array_map(static fn ($field) => $field->name, service('dashboard')->widget($id)->fields());

        $this->assertSame(['showTitle', 'showLegend', 'legendPosition', 'enableAnimation', 'tension'], $names('charts:testLine'));
        $this->assertSame(['showTitle', 'showLegend', 'legendPosition', 'enableAnimation', 'colorScheme'], $names('charts:testBar'));
    }

    public function testWidgetsTheViewerMayNotSeeAreLeftOutOfTheAvailableSet(): void
    {
        service('dashboard')->register(new TestWidget('test:logsOnly', 'logs.view'));
        $user = $this->createUser();
        $this->actingAs($user);

        $this->assertArrayNotHasKey('test:logsOnly', service('dashboard')->available());

        $user->addPermission('logs.view');

        $this->assertArrayHasKey('test:logsOnly', service('dashboard')->available());
    }

    public function testAnItemStaysAvailableWhateverSwitchIsStoredForIt(): void
    {
        $user = $this->createUser();
        $user->addPermission('users.list');
        $this->actingAs($user);
        setting('Stats.Stats_usersInRecycler693', false);

        $this->assertArrayHasKey('stats:usersInRecycler693', service('dashboard')->available());
    }

    private function bootWithTestCharts(): void
    {
        $this->withTestCharts();
        Services::resetSingle('widgets');
        Services::resetSingle('dashboard');
        (new Bonfire())->boot(true);
    }
}
