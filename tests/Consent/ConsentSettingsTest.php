<?php

namespace Tests\Consent;

use Bonfire\Consent\Database\Migrations\ForgetConsentText;
use Bonfire\Users\User;
use CodeIgniter\Config\Services;
use Config\Database;
use Tests\Support\SubmitsForms;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class ConsentSettingsTest extends TestCase
{
    use SubmitsForms;

    protected $refresh = true;
    private User $user;

    protected function tearDown(): void
    {
        Services::resetSingle('settings');

        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser();
        $this->user->addGroup('superadmin');
    }

    public function testEverySettingRoundTripsThroughTheForm(): void
    {
        $settings = [
            'Consent.requireConsent' => true,
            'Consent.consentLength'  => '30',
            'Consent.policyUrl'      => '/cookies',
        ];

        $this->assertRoundTrips($settings);
    }

    public function testConsentCanBeOffWithEmptyFields(): void
    {
        $this->assertRoundTrips([
            'Consent.requireConsent' => false,
            'Consent.consentLength'  => '',
            'Consent.policyUrl'      => '',
        ]);
    }

    public function testConsentTextIsNotEditable(): void
    {
        $html   = $this->actingAs($this->user)->get('/admin/settings/consent')->getBody();
        $fields = $this->formFields($html, site_url(ADMIN_AREA . '/settings/consent'));

        $this->assertSame(['requireConsent', 'consentLength', 'policyUrl'], array_values(array_diff(array_keys($fields), [csrf_token()])));
    }

    public function testMigrationForgetsTextSavedFromTheAdmin(): void
    {
        setting('Consent.consentMessage', 'We use cookies.');
        setting('Consent.policyTitle', 'Cookies');
        setting('Consent.consents', ['analytics' => ['name' => 'Analytics', 'desc' => 'Counts visits.']]);
        setting('Consent.policyUrl', '/cookies');

        (new ForgetConsentText(Database::forge()))->up();
        Services::resetSingle('settings');

        $this->assertSame('Consent.message', setting('Consent.consentMessage'));
        $this->assertSame('Consent.policyLinkTitle', setting('Consent.policyTitle'));
        $this->assertSame(config('Consent')->consents, setting('Consent.consents'));
        $this->assertSame('/cookies', setting('Consent.policyUrl'));
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function assertRoundTrips(array $settings): void
    {
        foreach ($settings as $key => $value) {
            setting($key, $value);
        }

        $html   = $this->actingAs($this->user)->get('/admin/settings/consent')->getBody();
        $fields = $this->formFields($html, site_url(ADMIN_AREA . '/settings/consent'));

        $this->actingAs($this->user)->post('/admin/settings/consent', $fields)->assertSessionMissing('errors');

        foreach ($settings as $key => $value) {
            $this->assertSame($value, setting($key), $key);
        }
    }
}
