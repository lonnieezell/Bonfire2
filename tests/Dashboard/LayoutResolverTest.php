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
use Tests\Support\RendersDashboard;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class LayoutResolverTest extends TestCase
{
    use RendersDashboard;

    private const QUICK_LINKS = 'cell:Bonfire\Dashboard\DashboardCells::quickLinks';

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
        auth()->logout();
        setting()->flush();

        parent::tearDown();
    }

    public function testTheDefaultLayoutIsThePlainCellsInOrder(): void
    {
        $layout = (new LayoutResolver(service('dashboard')))->resolve();

        $this->assertCount(1, $layout->sections);
        $this->assertSame('', $layout->sections[0]->title);
        $this->assertSame([self::QUICK_LINKS], $layout->ids());
        $this->assertSame(Size::Full, $layout->sections[0]->widgets[0]->size);
    }

    public function testItemsAreLeftOutOfTheDefaultLayout(): void
    {
        $resolver = new LayoutResolver(service('dashboard'));

        $this->assertNotContains('stats:usersInRecycler693', $resolver->resolve()->ids());
        $this->assertFalse($resolver->includes('stats:usersInRecycler693'));
        $this->assertTrue($resolver->includes(self::QUICK_LINKS));
    }

    public function testIncludesAPlacedItemBeforeItIsRegistered(): void
    {
        $this->placeForAUser('stats:lateComer');

        $this->assertTrue((new LayoutResolver(service('dashboard')))->includes('stats:lateComer'));
    }

    public function testAMalformedStoredLayoutFallsBackToTheDefault(): void
    {
        auth()->login($this->createUser());
        setting()->set('Dashboard.layout', ['version' => '1', 'sections' => [['widgets' => [['id' => 'stats:usersInRecycler693']]]]], LayoutResolver::userContext());

        $resolver = new LayoutResolver(service('dashboard'));

        $this->assertSame([self::QUICK_LINKS], $resolver->resolve()->ids());
        $this->assertFalse($resolver->includes('stats:usersInRecycler693'));
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
        auth()->login($user);
        $this->placeOnDashboard($user, $id);
    }
}
