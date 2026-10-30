<?php

namespace Tests\Privacy;

use Bonfire\Consent\ConsentPersonalData;
use Bonfire\Privacy\PersonalDataProvider;
use Bonfire\Privacy\PrunesPersonalData;
use Bonfire\Users\User;
use Bonfire\Users\UserPersonalData;
use CodeIgniter\Config\Services;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\StreamFilterTrait;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class PruneTest extends TestCase
{
    use StreamFilterTrait;

    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();

        Services::resetSingle('personalData');
        service('personalData')
            ->register('users', new UserPersonalData())
            ->register('consent', new ConsentPersonalData());
    }

    protected function tearDown(): void
    {
        Services::resetSingle('personalData');
        Services::resetSingle('settings');

        parent::tearDown();
    }

    public function testDeletesLoginsOlderThanTheRetention(): void
    {
        $this->login('auth_logins', 91, '10.0.0.1');
        $this->login('auth_logins', 89, '10.0.0.2');
        $this->login('auth_token_logins', 91, '10.0.0.3');
        $this->login('auth_token_logins', 89, '10.0.0.4');

        service('personalData')->prune();

        $this->assertSame(['10.0.0.2'], array_column($this->db->table('auth_logins')->get()->getResultArray(), 'ip_address'));
        $this->assertSame(['10.0.0.4'], array_column($this->db->table('auth_token_logins')->get()->getResultArray(), 'ip_address'));
    }

    public function testFollowsTheConfiguredRetention(): void
    {
        config('Privacy')->retention['logins'] = 10;
        $this->login('auth_logins', 11, '10.0.0.1');
        $this->login('auth_logins', 9, '10.0.0.2');

        service('personalData')->prune();

        $this->assertSame(['10.0.0.2'], array_column($this->db->table('auth_logins')->get()->getResultArray(), 'ip_address'));
    }

    public function testNullRetentionKeepsTheData(): void
    {
        config('Privacy')->retention = ['logins' => null, 'tokenLogins' => null, 'consents' => null];
        $this->login('auth_logins', 5000, '10.0.0.1');
        $this->login('auth_token_logins', 5000, '10.0.0.2');
        $this->consent('a', 5000);

        service('personalData')->prune();

        $this->assertSame(1, $this->db->table('auth_logins')->countAllResults());
        $this->assertSame(1, $this->db->table('auth_token_logins')->countAllResults());
        $this->assertSame(1, $this->db->table('consent_records')->countAllResults());
    }

    public function testDeletesSupersededConsentRecordsAndKeepsTheLatestWhileValid(): void
    {
        setting('Consent.consentLength', 1000);
        $this->consent('a', 400, 'old');
        $this->consent('a', 300, 'old but recent enough');
        $this->consent('b', 400, 'latest, still valid');
        $this->consent('c', 1001, 'latest, expired');

        service('personalData')->prune();

        $this->assertSame(
            ['old but recent enough', 'latest, still valid'],
            array_column($this->db->table('consent_records')->orderBy('id')->get()->getResultArray(), 'consents'),
        );
    }

    public function testABlankConsentLengthKeepsEachBrowsersLatestRecord(): void
    {
        setting('Consent.consentLength', '');
        $this->consent('a', 400, 'old');
        $this->consent('a', 399, 'latest');

        service('personalData')->prune();

        $this->assertSame(['latest'], array_column($this->db->table('consent_records')->get()->getResultArray(), 'consents'));
    }

    public function testTheCommandPrunesModulesAndReportsCounts(): void
    {
        $this->login('auth_logins', 91, '10.0.0.1');
        service('personalData')->register('orders', new class () implements PersonalDataProvider, PrunesPersonalData {
            public function label(): string
            {
                return 'Orders';
            }

            public function export(User $user): array
            {
                return [];
            }

            public function erase(User $user): void
            {
            }

            public function prune(): int
            {
                return 7;
            }
        });

        command('bf:privacy:prune');

        $output = $this->getStreamFilterBuffer();
        $this->assertMatchesRegularExpression('/users\D+1\b/', $output);
        $this->assertMatchesRegularExpression('/orders\D+7\b/', $output);
        $this->assertEqualsWithDelta(Time::now()->getTimestamp(), service('personalData')->lastPruned()->getTimestamp(), 5);
    }

    public function testHasNeverPrunedUntilItRuns(): void
    {
        $this->assertNull(service('personalData')->lastPruned());
    }

    private function login(string $table, int $daysAgo, string $ip): void
    {
        $this->db->table($table)->insert([
            'ip_address' => $ip, 'user_agent' => 'Firefox', 'id_type' => 'email_password', 'identifier' => 'jo@example.com',
            'user_id'    => null, 'date' => Time::now()->subDays($daysAgo)->toDateTimeString(), 'success' => 0,
        ]);
    }

    private function consent(string $id, int $daysAgo, string $label = '{}'): void
    {
        $this->db->table('consent_records')->insert([
            'consent_id' => str_repeat($id, 32), 'consents' => $label, 'created_at' => Time::now()->subDays($daysAgo)->toDateTimeString(),
        ]);
    }
}
