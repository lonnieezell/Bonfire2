<?php

namespace Tests\Core;

use Bonfire\Core\Namespaces;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class NamespacesTest extends CIUnitTestCase
{
    public function testRegistersEachBonfireModuleUnderItsNamespace(): void
    {
        Namespaces::register();

        $this->assertSame([realpath(__DIR__ . '/../../src/Users')], service('autoloader')->getNamespace('Bonfire\Users'));
    }

    public function testRegistersEachAppModuleUnderItsNamespace(): void
    {
        config('Bonfire')->appModules = ['Tests\Support\Modules' => SUPPORTPATH . 'Modules'];

        Namespaces::register();

        $this->assertSame([realpath(SUPPORTPATH . 'Modules/Charts')], service('autoloader')->getNamespace('Tests\Support\Modules\Charts'));
    }

    public function testModuleMigrationsCanBeFoundByModuleNamespace(): void
    {
        Namespaces::register();

        $migrations = service('migrations')->setNamespace('Bonfire\Actions')->findMigrations();

        $this->assertContains('Bonfire\Actions\Database\Migrations\CreateActionRunsTable', array_column($migrations, 'class'));
    }
}
