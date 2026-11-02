<?php

declare(strict_types=1);

namespace Tests\Email;

use Bonfire\Email\LegacySettingsTransport;
use CodeIgniter\Config\Factories;
use CodeIgniter\Events\Events;
use CodeIgniter\Settings\Settings;
use Config\Services;
use RuntimeException;
use Tests\Support\TestCase;

/**
 * Runs without migrations, as on a fresh install: there is no settings table.
 *
 * @internal
 */
final class LegacyBridgeBootTest extends TestCase
{
    protected $migrate = false;
    protected $refresh = false;

    protected function tearDown(): void
    {
        Services::resetSingle('settings');
        Factories::reset('config');

        parent::tearDown();
    }

    public function testRegisteringTouchesNeitherSettingsNorTheDatabase(): void
    {
        Services::injectMock('settings', new class (config('Settings')) extends Settings {
            public function get(string $key, ?string $context = null): never
            {
                throw new RuntimeException('Settings were read while booting.');
            }
        });

        $queries = 0;
        $counter = static function () use (&$queries): void {
            $queries++;
        };
        Events::on('DBQuery', $counter);

        LegacySettingsTransport::register();

        Events::removeListener('DBQuery', $counter);

        $this->assertSame(0, $queries);
        $this->assertSame('legacy', config('Mailer')->default);
    }
}
