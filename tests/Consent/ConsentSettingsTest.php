<?php

namespace Tests\Consent;

use Bonfire\Users\User;
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
            'Consent.policyTitle'    => 'Cookie Policy',
            'Consent.consentMessage' => 'We use cookies. See {policy_url}.',
            'Consent.consents'       => ['analytics' => ['name' => 'Analytics', 'desc' => 'Counts visits.']],
        ];

        $this->assertRoundTrips($settings);
    }

    public function testConsentCanBeOffWithEmptyFields(): void
    {
        $this->assertRoundTrips([
            'Consent.requireConsent' => false,
            'Consent.consentLength'  => '',
            'Consent.policyUrl'      => '',
            'Consent.policyTitle'    => '',
            'Consent.consentMessage' => '',
        ]);
    }

    public function testShowsALiveBannerPreview(): void
    {
        setting('Consent.consentMessage', 'We use cookies. See {policy_url}.');

        $html = $this->actingAs($this->user)->get('/admin/settings/consent')->getBody();

        $this->assertMatchesRegularExpression('#<div class="bf-consent-preview"[^>]*>#', $html);
        $this->assertStringContainsString('x-model="message"', $html);
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
            $this->assertEquals($value, setting($key), $key);
        }
    }
}
