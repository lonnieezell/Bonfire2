<?php

namespace Tests\Consent;

use Bonfire\Consent\Filters\ConsentFilter;
use CodeIgniter\Config\Services;
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
        Services::resetSingle('settings');

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

    public function testUnansweredVisitorsSeeAFormThatPostsToTheSaveRoute(): void
    {
        $form = $this->popup();

        $this->assertStringNotContainsString(' hidden', $form);
        $this->assertStringContainsString('method="post"', $form);
        $this->assertStringContainsString('action="' . url_to('consent-save') . '"', $form);
        $this->assertStringContainsString('name="' . csrf_token() . '"', $form);
    }

    public function testAnsweredVisitorsGetTheFormHiddenAndPreFilled(): void
    {
        $this->answered(['required' => 1, 'performance' => 1, 'targeting' => 0]);
        helper('consent');

        $form = $this->popup($this->filtered('<html><head></head><body>' . consent_settings_link() . '</body></html>'));

        $this->assertMatchesRegularExpression('#<form[^>]* hidden#', $form);
        $this->assertTrue($this->isChecked($form, 'performance'));
        $this->assertFalse($this->isChecked($form, 'targeting'));
    }

    public function testAnsweredVisitorsGetNoFormOnPagesWithoutASettingsLink(): void
    {
        $this->answered(['required' => 1, 'performance' => 1, 'targeting' => 0]);

        $this->get('/')->assertDontSee('id="consent-popup"');
    }

    public function testANewConsentAsksAgainWithTheNewOneUnchecked(): void
    {
        $this->answered(['required' => 1, 'performance' => 1]);

        $form = $this->popup();

        $this->assertDoesNotMatchRegularExpression('#<form[^>]* hidden#', $form);
        $this->assertTrue($this->isChecked($form, 'performance'));
        $this->assertFalse($this->isChecked($form, 'targeting'));
    }

    public function testLockedConsentsAreCheckedAndCannotBeChanged(): void
    {
        setting('Consent.consents', [
            'essential' => ['locked' => true, 'name' => 'Essential', 'desc' => 'Keeps you signed in.'],
            'required'  => ['name' => 'Required', 'desc' => 'No longer special.'],
        ]);

        $form = $this->popup();

        $this->assertMatchesRegularExpression('#<input[^>]*name="consents\[essential\]"[^>]*checked disabled#', $form);
        $this->assertDoesNotMatchRegularExpression('#<input[^>]*name="consents\[required\]"[^>]*disabled#', $form);
    }

    public function testDefaultTextIsTranslated(): void
    {
        service('language')->setLocale('it');

        try {
            $form = $this->popup();
        } finally {
            Services::resetSingle('language');
        }

        $this->assertStringContainsString(esc(lang('Consent.performanceName', [], 'it')), $form);
        $this->assertStringContainsString(lang('Consent.policyLinkTitle', [], 'it'), $form);
        $this->assertNotSame(lang('Consent.performanceName', [], 'it'), lang('Consent.performanceName', [], 'en'));
    }

    public function testLiteralTextRendersAsIs(): void
    {
        setting('Consent.consents', ['analytics' => ['name' => 'Analytics', 'desc' => 'Counts visits. See e.g. the stats.']]);

        $form = $this->popup();

        $this->assertStringContainsString('Analytics', $form);
        $this->assertStringContainsString('Counts visits. See e.g. the stats.', $form);
    }

    public function testSettingsLinkOpensTheForm(): void
    {
        helper('consent');

        $this->assertStringContainsString('data-consent-open', consent_settings_link());

        setting('Consent.requireConsent', false);
        $this->assertSame('', consent_settings_link());
    }

    /**
     * @param array<string, int> $choices
     */
    private function answered(array $choices): void
    {
        service('superglobals')->setCookie('bf_consent', json_encode(['consent' => 1, 'consent_id' => str_repeat('a', 32)] + $choices));
    }

    private function popup(?string $body = null): string
    {
        $body ??= $this->get('/')->getBody();

        $this->assertMatchesRegularExpression('#<form id="consent-popup".*?</form>#s', $body);
        preg_match('#<form id="consent-popup".*?</form>#s', $body, $match);

        return $match[0];
    }

    private function filtered(string $body): string
    {
        $response = service('response')->setBody($body);

        return (string) (new ConsentFilter())->after(service('request'), $response)->getBody();
    }

    private function isChecked(string $form, string $alias): bool
    {
        preg_match('#<input[^>]*name="consents\[' . $alias . '\]"[^>]*>#', $form, $input);

        return str_contains($input[0], 'checked');
    }

    private function assertPolicyLink(string $url): void
    {
        $this->assertMatchesRegularExpression('#href=["\']' . preg_quote($url, '#') . '["\']#', $this->get('/')->getBody());
    }
}
