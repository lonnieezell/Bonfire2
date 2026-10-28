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
use CodeIgniter\Config\Factories;
use Bonfire\Dashboard\Widgets\QuickLinksWidget;
use Config\Services;
use Tests\Support\Libraries\StubDashboard;
use Tests\Support\RendersDashboard;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class LayoutResolverTest extends TestCase
{
    use RendersDashboard;

    private const QUICK_LINKS = 'cell:Bonfire\Dashboard\DashboardCells::quickLinks';
    private const DEFAULT_IDS = [self::QUICK_LINKS, 'stats:usersExpTable693', 'stats:usersInRecycler693', 'logs:logActivity'];

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
        Factories::reset('config');
        auth()->logout();
        setting()->flush();

        parent::tearDown();
    }

    public function testTheDefaultLayoutIsQuickLinksThenUsersThenMonitoring(): void
    {
        $user = $this->createUser();
        $user->addGroup('superadmin');
        auth()->login($user);

        $layout = (new LayoutResolver(service('dashboard')))->resolve();

        $this->assertSame(['', 'Users', 'Monitoring'], array_column($layout->sections, 'title'));
        $this->assertSame(self::DEFAULT_IDS, $layout->ids());
        $this->assertSame(
            [Size::Full, Size::Half, Size::Half, Size::Full],
            array_merge(...array_map(static fn ($section) => array_column($section->widgets, 'size'), $layout->sections)),
        );
    }

    public function testWithoutAConfiguredLayoutTheDefaultIsThePlainCellsInOrder(): void
    {
        config('Dashboard')->layout = null;
        setting('Dashboard.cells', ['App\Cells\Weather::forecast', 'Bonfire\Widgets\Cells\WidgetCells::stats', 'Bonfire\Dashboard\DashboardCells::quickLinks']);

        $layout = (new LayoutResolver(service('dashboard')))->resolve();

        $this->assertCount(1, $layout->sections);
        $this->assertSame('', $layout->sections[0]->title);
        $this->assertSame(['cell:App\Cells\Weather::forecast', self::QUICK_LINKS], $layout->ids());
        $this->assertSame(Size::Full, $layout->sections[0]->widgets[0]->size);
    }

    public function testAnInstallListingTheOldQuickLinksCellKeepsItInPlaceOnce(): void
    {
        config('Dashboard')->layout = null;
        setting('Dashboard.cells', ['Bonfire\Widgets\Cells\WidgetCells::stats', 'Bonfire\Dashboard\DashboardCells::quickLinks']);

        $resolver = new LayoutResolver(service('dashboard'));

        $this->assertSame([self::QUICK_LINKS], $resolver->resolve()->ids());
        $this->assertInstanceOf(QuickLinksWidget::class, service('dashboard')->widget(self::QUICK_LINKS));
        $this->assertTrue($resolver->includes(self::QUICK_LINKS));
    }

    public function testOnlyItemsInTheDefaultLayoutAreIncluded(): void
    {
        $resolver = new LayoutResolver(service('dashboard'));

        $this->assertNotContains('charts:usersByGroupBar345', $resolver->resolve()->ids());
        $this->assertFalse($resolver->includes('charts:usersByGroupBar345'));
        $this->assertTrue($resolver->includes('stats:usersInRecycler693'));
        $this->assertTrue($resolver->includes(self::QUICK_LINKS));
    }

    public function testAUsersLayoutComesFirstThenTheStoredSiteDefaultThenTheConfiguredOne(): void
    {
        $resolver = new LayoutResolver(service('dashboard'));
        $this->placeForAUser('stats:userGroups643');
        $this->assertSame(['stats:userGroups643'], $resolver->resolve()->ids());

        setting('Dashboard.layout', ['version' => 1, 'sections' => [['title' => '', 'widgets' => [['id' => 'stats:usersInRecycler693']]]]]);
        $this->assertSame(['stats:userGroups643'], $resolver->resolve()->ids());

        setting()->forget('Dashboard.layout', LayoutResolver::userContext());
        $this->assertSame(['stats:usersInRecycler693'], $resolver->resolve()->ids());
        $this->assertTrue($resolver->includes('stats:usersInRecycler693'));
        $this->assertFalse($resolver->includes('logs:logActivity'));

        setting()->forget('Dashboard.layout');
        $this->assertSame(self::DEFAULT_IDS, $resolver->resolve()->ids());
    }

    public function testTheDefaultLayoutIgnoresTheUsersOwn(): void
    {
        $this->placeForAUser('charts:usersByGroupBar345');

        $this->assertSame(self::DEFAULT_IDS, (new LayoutResolver(service('dashboard')))->defaultLayout()->ids());
    }

    public function testIncludesAPlacedItemBeforeItIsRegistered(): void
    {
        $this->placeForAUser('stats:lateComer');

        $this->assertTrue((new LayoutResolver(service('dashboard')))->includes('stats:lateComer'));
    }

    public function testAMalformedStoredLayoutFallsBackToTheDefault(): void
    {
        $user = $this->createUser();
        $user->addGroup('superadmin');
        auth()->login($user);
        setting()->set('Dashboard.layout', ['version' => '1', 'sections' => [['widgets' => [['id' => 'charts:usersByGroupBar345']]]]], LayoutResolver::userContext());

        $resolver = new LayoutResolver(service('dashboard'));

        $this->assertSame(self::DEFAULT_IDS, $resolver->resolve()->ids());
        $this->assertFalse($resolver->includes('charts:usersByGroupBar345'));
    }

    public function testTheContextShowsPlacedItemsThatAreNotSwitchedOff(): void
    {
        StubDashboard::inject(true);
        $this->placeForAUser('stats:usersInRecycler693');
        $settings = new ItemSettings(WidgetKind::Stats, 'usersInRecycler693');

        $this->assertTrue(service('dashboardContext')->shows($settings));

        $settings->store(false);

        $this->assertFalse(service('dashboardContext')->shows($settings));
    }

    private function placeForAUser(string $id): void
    {
        $user = $this->createUser();
        // Log activity, in the default layout, needs logs.view
        $user->addGroup('superadmin');
        auth()->login($user);
        $this->placeOnDashboard($user, $id);
    }
}
