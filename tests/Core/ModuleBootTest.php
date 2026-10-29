<?php

namespace Tests\Core;

use Bonfire\Bonfire;
use Config\Services;
use Tests\Support\Packages\Courier\Module as CourierModule;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class ModuleBootTest extends TestCase
{
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();

        // createApplication() booted already, and earlier tests may have filled the admin menus and widgets
        // or signed a user in
        CourierModule::$calls = [];

        foreach (['auth', 'bonfire', 'menus', 'widgets'] as $service) {
            Services::resetSingle($service);
        }

        config('Bonfire')->packageModules = ['Tests\Support\Packages\Courier' => SUPPORTPATH . 'Packages/Courier'];
    }

    protected function tearDown(): void
    {
        foreach (['menus', 'widgets'] as $service) {
            Services::resetSingle($service);
        }

        parent::tearDown();
    }

    public function testPublicRequestInitsModulesForTheFront(): void
    {
        $this->get('/')->assertOK();

        $this->assertSame(['initFront'], CourierModule::$calls);
    }

    public function testPublicPathNamingTheAdminAreaInitsModulesForTheFront(): void
    {
        $this->withRoutes([['get', 'about/' . ADMIN_AREA . '-team', static fn () => 'Team']])
            ->get('about/' . ADMIN_AREA . '-team')
            ->assertOK();

        $this->assertSame(['initFront'], CourierModule::$calls);
    }

    public function testPublicRequestSetsUpNoAdminMenusOrWidgets(): void
    {
        $this->get('/')->assertOK();

        $this->assertSame([], service('menus')->menu('sidebar')->collections());
        $this->assertSame([], service('widgets')->items());
    }

    public function testAdminRequestInitsModulesForTheAdminOnce(): void
    {
        $admin = $this->createUser();
        $admin->addGroup('superadmin');

        $this->actingAs($admin)->get(ADMIN_AREA)->assertOK();

        $this->assertSame(['initAdmin'], CourierModule::$calls);
    }

    public function testGuestAdminRequestRedirectsBeforeInitingModules(): void
    {
        $this->get(ADMIN_AREA)->assertStatus(302);

        $this->assertSame([], CourierModule::$calls);
    }

    public function testAppModulesAreDiscoveredFromTheCache(): void
    {
        $this->withTestCharts();
        (new Bonfire())->boot(true);

        // A cached search finds the Charts module even once its directory is no longer configured
        config('Bonfire')->appModules = [];
        Services::resetSingle('widgets');
        (new Bonfire())->boot(true);

        $ids = array_map(static fn ($item) => $item->id(), service('widgets')->widget('charts')->collection('charts')->items());
        $this->assertContains('testLine', $ids);
    }
}
