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
        $this->assertSame(Size::Small, $widget->sizes()->default);
        $this->assertNull($widget->permission());
    }

    public function testChartsItemsTakeTheirDefaultSizeFromTheirCssClass(): void
    {
        $widget = service('dashboard')->widget('charts:usersByGroupBar345');

        $this->assertNotNull($widget);
        $this->assertSame('charts', $widget->group());
        $this->assertSame(Size::Medium, $widget->sizes()->default);
    }

    public function testLogActivityBecomesAWidget(): void
    {
        $widget = service('dashboard')->widget('logs:logActivity');

        $this->assertNotNull($widget);
        $this->assertSame('charts', $widget->group());
        $this->assertSame(Size::Full, $widget->sizes()->default);
    }

    public function testCustomCellsBecomeWidgets(): void
    {
        $widget = service('dashboard')->widget('cell:Bonfire\Dashboard\DashboardCells::quickLinks');

        $this->assertNotNull($widget);
        $this->assertSame('custom', $widget->group());
        $this->assertSame(Size::Full, $widget->sizes()->default);
    }

    public function testUnavailableItemsAreLeftOutOfTheAvailableSet(): void
    {
        setting('Stats.Stats_usersInRecycler693', 'on');

        $available = service('dashboard')->available();

        $this->assertArrayHasKey('stats:usersInRecycler693', $available);
        $this->assertArrayNotHasKey('stats:usersExpTable693', $available);
    }
}
