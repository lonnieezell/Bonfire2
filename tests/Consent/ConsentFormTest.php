<?php

namespace Tests\Consent;

use Tests\Support\TestCase;

/**
 * @internal
 */
final class ConsentFormTest extends TestCase
{
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();

        service('superglobals')->setCookieArray([]);
        setting('Consent.requireConsent', true);
    }

    protected function tearDown(): void
    {
        service('superglobals')->setCookieArray([]);

        parent::tearDown();
    }

    public function testAbsolutePolicyUrlIsUsedAsIs(): void
    {
        setting('Consent.policyUrl', 'https://example.org/cookies');

        $this->assertPolicyLink('https://example.org/cookies');
    }

    public function testRelativePolicyUrlBecomesASiteUrl(): void
    {
        setting('Consent.policyUrl', 'cookies');

        $this->assertPolicyLink(site_url('cookies'));
    }

    public function testFormIsHiddenOnceConsentIsRejected(): void
    {
        service('superglobals')->setCookie('bf_consent', json_encode(['consent' => 1, 'required' => 1, 'performance' => 0, 'targeting' => 0]));

        $this->get('/')->assertDontSee('id="consent-popup"');
    }

    private function assertPolicyLink(string $url): void
    {
        $this->assertMatchesRegularExpression('#href=["\']' . preg_quote($url, '#') . '["\']#', $this->get('/')->getBody());
    }
}
