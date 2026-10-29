<?php

namespace Tests\Core;

use Bonfire\Bonfire;
use Bonfire\Config\Bonfire as BonfireConfig;
use CodeIgniter\Config\BaseConfig;
use Config\Services;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Packages\Courier\Config\Registrar as CourierRegistrar;
use Tests\Support\Packages\Courier\Module as CourierModule;
use Tests\Support\Packages\Published\Bonfire as PublishedConfig;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class PackageModulesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        CourierModule::$calls = [];

        // An earlier test can leave an admin URL as the current one, which makes boot() init the admin
        foreach (['uri', 'request'] as $service) {
            Services::resetSingle($service);
        }
    }

    protected function tearDown(): void
    {
        // Booting the admin filled these shared services for this test only
        foreach (['menus', 'widgets'] as $service) {
            Services::resetSingle($service);
        }

        parent::tearDown();
    }

    public function testAdminBootInitsAPackageModule(): void
    {
        config('Bonfire')->packageModules = ['Tests\Support\Packages\Courier' => SUPPORTPATH . 'Packages/Courier'];

        (new Bonfire())->boot(true);

        $this->assertSame(['initAdmin'], CourierModule::$calls);
    }

    public function testFrontBootInitsAPackageModule(): void
    {
        config('Bonfire')->packageModules = ['Tests\Support\Packages\Courier' => SUPPORTPATH . 'Packages/Courier'];

        (new Bonfire())->boot();

        $this->assertSame(['initFront'], CourierModule::$calls);
    }

    public function testEntriesWithoutAModuleFileAreSkipped(): void
    {
        config('Bonfire')->packageModules = [
            'Acme\Missing'                   => SUPPORTPATH . 'Packages/DoesNotExist',
            'Acme\NoModule'                  => SUPPORTPATH . 'Packages/NoModule',
            'Tests\Support\Packages\Courier' => SUPPORTPATH . 'Packages/Courier',
        ];

        (new Bonfire())->boot(true);

        $this->assertSame(['initAdmin'], CourierModule::$calls);
    }

    /**
     * @param class-string<BonfireConfig> $configClass
     */
    #[DataProvider('provideARegistrarAddsAPackageModule')]
    public function testARegistrarAddsAPackageModule(string $configClass): void
    {
        $registrars               = BaseConfig::$registrars;
        BaseConfig::$registrars[] = new CourierRegistrar();

        try {
            $config = new $configClass();
        } finally {
            BaseConfig::$registrars = $registrars;
        }

        $this->assertSame(
            SUPPORTPATH . 'Packages/Courier',
            $config->packageModules['Tests\Support\Packages\Courier'] ?? null,
        );
    }

    public static function provideARegistrarAddsAPackageModule(): iterable
    {
        yield 'package config' => [BonfireConfig::class];

        yield 'published config' => [PublishedConfig::class];
    }
}
