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
use Bonfire\Dashboard\LayoutResolver;
use Bonfire\Dashboard\Size;
use Bonfire\Widgets\ItemSettings;
use Bonfire\Widgets\WidgetKind;
use Config\Services;
use Tests\Support\Libraries\StubDashboard;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class LayoutResolverTest extends TestCase
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

        Services::resetSingle('dashboardContext');
        setting()->flush();

        parent::tearDown();
    }

    public function testTheDefaultLayoutIsTheCellsInOrderWithEnabledItemsExpanded(): void
    {
        setting('Stats.Stats_usersInRecycler693', 'on');
        setting('Stats.Logs_logActivity', 'on');
        setting('Stats.Charts_usersByGroupBar345', 'on');

        $layout = (new LayoutResolver(service('dashboard')))->resolve();

        $this->assertCount(1, $layout->sections);
        $this->assertSame('', $layout->sections[0]->title);
        $this->assertSame([
            'cell:Bonfire\Dashboard\DashboardCells::quickLinks',
            'stats:usersInRecycler693',
            'logs:logActivity',
            'charts:usersByGroupBar345',
        ], $layout->ids());
    }

    public function testWidgetsTakeTheirDefaultSize(): void
    {
        setting('Stats.Stats_usersInRecycler693', 'on');

        $widgets = (new LayoutResolver(service('dashboard')))->resolve()->sections[0]->widgets;

        $this->assertSame(Size::Full, $widgets[0]->size);
        $this->assertSame(Size::Small, $widgets[1]->size);
    }

    public function testADisabledItemIsNotInTheDefaultLayout(): void
    {
        $layout = (new LayoutResolver(service('dashboard')))->resolve();

        $this->assertNotContains('stats:usersInRecycler693', $layout->ids());
        $this->assertFalse((new LayoutResolver(service('dashboard')))->includes('stats:usersInRecycler693'));
    }

    public function testAnEnabledItemIsLeftOutWhenItsCellIsNot(): void
    {
        setting('Stats.Stats_usersInRecycler693', 'on');
        setting('Dashboard.cells', ['Bonfire\Dashboard\DashboardCells::quickLinks']);

        $resolver = new LayoutResolver(service('dashboard'));

        $this->assertSame(['cell:Bonfire\Dashboard\DashboardCells::quickLinks'], $resolver->resolve()->ids());
        $this->assertFalse($resolver->includes('stats:usersInRecycler693'));
    }

    public function testIncludesAnItemBeforeItIsRegistered(): void
    {
        setting('Stats.Stats_lateComer', 'on');

        $this->assertTrue((new LayoutResolver(service('dashboard')))->includes('stats:lateComer'));
    }

    public function testTheContextShowsOnlyItemsInTheResolvedLayout(): void
    {
        StubDashboard::inject(true);
        setting('Stats.Stats_usersInRecycler693', 'on');
        $settings = new ItemSettings(WidgetKind::Stats, 'usersInRecycler693');

        $this->assertTrue(service('dashboardContext')->shows($settings));

        setting('Dashboard.cells', ['Bonfire\Dashboard\DashboardCells::quickLinks']);

        $this->assertFalse(service('dashboardContext')->shows($settings));
    }
}
