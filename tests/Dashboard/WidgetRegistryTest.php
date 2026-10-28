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
        setting('Stats.Stats_usersInRecycler693', 'on');

        $widget = service('dashboard')->widget('stats:usersInRecycler693');

        $this->assertNotNull($widget);
        $this->assertSame('stats', $widget->group());
        $this->assertSame('fa fa-users', $widget->icon());
        $this->assertSame(Size::Quarter, $widget->sizes()->default);
    }

    public function testTheBuiltInUsersAndGroupsWidgetsDeclarePermissions(): void
    {
        $this->assertSame('users.list', service('dashboard')->widget('stats:usersInRecycler693')->permission());
        $this->assertSame('users.list', service('dashboard')->widget('stats:usersExpTable693')->permission());
        $this->assertSame('groups.settings', service('dashboard')->widget('stats:userGroups643')->permission());
        $this->assertSame('groups.settings', service('dashboard')->widget('charts:usersByGroupBar345')->permission());
        $this->assertNull(service('dashboard')->widget('cell:Bonfire\Dashboard\DashboardCells::quickLinks')->permission());
    }

    public function testChartsItemsTakeTheirDefaultSizeFromTheirCssClass(): void
    {
        $widget = service('dashboard')->widget('charts:usersByGroupBar345');

        $this->assertNotNull($widget);
        $this->assertSame('charts', $widget->group());
        $this->assertSame(Size::Half, $widget->sizes()->default);
    }

    public function testLogActivityBecomesAWidget(): void
    {
        $widget = service('dashboard')->widget('logs:logActivity');

        $this->assertNotNull($widget);
        $this->assertSame('charts', $widget->group());
        $this->assertSame(Size::Full, $widget->sizes()->default);
    }

    public function testQuickLinksIsANativeWidgetUnderItsCellId(): void
    {
        setting('Dashboard.cells', []);

        $widget = service('dashboard')->widget('cell:Bonfire\Dashboard\DashboardCells::quickLinks');

        $this->assertInstanceOf(QuickLinksWidget::class, $widget);
        $this->assertSame('quickLinks', $widget->group());
        $this->assertSame(['quickLinks'], array_keys(array_intersect_key(service('dashboard')->grouped(), ['quickLinks' => 1, 'custom' => 1])));
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
        foreach (['stats:usersInRecycler693', 'charts:usersByGroupBar345', 'logs:logActivity', 'cell:Bonfire\Dashboard\DashboardCells::quickLinks'] as $id) {
            $this->assertNotSame('', service('dashboard')->widget($id)->description(), $id);
        }
    }

    public function testEachChartTypeOffersItsOwnSettings(): void
    {
        $names = static fn (string $id) => array_map(static fn ($field) => $field->name, service('dashboard')->widget($id)->fields());

        $this->assertSame(['showTitle', 'showLegend', 'legendPosition', 'enableAnimation', 'tension'], $names('charts:usersByGroupLine123'));
        $this->assertSame(['showTitle', 'showLegend', 'legendPosition', 'enableAnimation', 'colorScheme'], $names('charts:usersByGroupBar345'));
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

    public function testSwitchedOffItemsAreLeftOutOfTheAvailableSet(): void
    {
        $user = $this->createUser();
        $user->addPermission('users.list');
        $this->actingAs($user);
        setting('Stats.Stats_usersExpTable693', false);

        $available = service('dashboard')->available();

        $this->assertArrayHasKey('stats:usersInRecycler693', $available);
        $this->assertArrayNotHasKey('stats:usersExpTable693', $available);
    }
}
