<?php

namespace Tests\Groups;

use Bonfire\Auth\Config\AuthGroups;
use CodeIgniter\Config\BaseConfig;
use Tests\Support\Packages\Courier\Config\Registrar as CourierRegistrar;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class ModulePermissionsTest extends TestCase
{
    public function testARegistrarAddsPermissionsAndKeepsTheInstallsOwn(): void
    {
        $config = $this->configWithCourier();

        $this->assertArrayHasKey('courier.send', $config->permissions);
        $this->assertArrayHasKey('admin.access', $config->permissions);
    }

    public function testARegistrarDeclaresGrantsWithoutTouchingTheMatrix(): void
    {
        $config = $this->configWithCourier();

        $this->assertSame(['superadmin' => ['courier.*'], 'admin' => ['courier.send']], $config->moduleGrants['courier']);
        $this->assertNotContains('courier.send', $config->matrix['admin']);
    }

    private function configWithCourier(): AuthGroups
    {
        $registrars               = BaseConfig::$registrars;
        BaseConfig::$registrars[] = new CourierRegistrar();

        try {
            return new AuthGroups();
        } finally {
            BaseConfig::$registrars = $registrars;
        }
    }
}
