<?php

namespace Tests\Consent;

use CodeIgniter\Config\Services;
use CodeIgniter\Test\TestResponse;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class ConsentSaveTest extends TestCase
{
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();

        service('superglobals')->setCookieArray([]);
        setting('Consent.requireConsent', true);
        auth()->logout();
    }

    protected function tearDown(): void
    {
        service('superglobals')->setCookieArray([]);
        Services::resetSingle('settings');

        parent::tearDown();
    }

    public function testAcceptingAllSetsASiteWideCookieAndRedirectsBack(): void
    {
        setting('Consent.consentLength', 30);

        $response = $this->post('consent', ['choice' => 'accept']);

        $response->assertRedirect();
        $cookie = $response->response()->getCookie('bf_consent');
        $this->assertSame('/', $cookie->getPath());
        $this->assertSame('Lax', $cookie->getSameSite());
        $this->assertFalse($cookie->isSecure());
        $this->assertEqualsWithDelta(time() + 30 * DAY, $cookie->getExpiresTimestamp(), 5);
        $this->assertSame(['required' => 1, 'performance' => 1, 'targeting' => 1], $this->choices($response));
    }

    public function testCookieIsSecureOverHttps(): void
    {
        service('superglobals')->setServer('HTTPS', 'on');

        try {
            $response = $this->post('consent', ['choice' => 'accept']);
        } finally {
            service('superglobals')->unsetServer('HTTPS');
        }

        $this->assertTrue($response->response()->getCookie('bf_consent')->isSecure());
    }

    public function testRejectingKeepsLockedConsents(): void
    {
        $response = $this->post('consent', ['choice' => 'reject']);

        $this->assertSame(['required' => 1, 'performance' => 0, 'targeting' => 0], $this->choices($response));
    }

    public function testCustomSavesTheCheckedConsentsAndCannotRejectALockedOne(): void
    {
        $response = $this->post('consent', ['choice' => 'custom', 'consents' => ['performance' => '1']]);

        $this->assertSame(['required' => 1, 'performance' => 1, 'targeting' => 0], $this->choices($response));
    }

    public function testClosingRejectsEverythingNotYetAccepted(): void
    {
        $this->assertSame(
            ['required' => 1, 'performance' => 0, 'targeting' => 0],
            $this->choices($this->post('consent', ['choice' => 'close'])),
        );

        service('superglobals')->setCookie('bf_consent', json_encode(['consent' => 1, 'required' => 1, 'performance' => 1]));

        $this->assertSame(
            ['required' => 1, 'performance' => 1, 'targeting' => 0],
            $this->choices($this->post('consent', ['choice' => 'close'])),
        );
    }

    public function testWithdrawnConsentIsGoneOnTheNextRequest(): void
    {
        helper('consent');
        service('superglobals')->setCookie('bf_consent', json_encode(['consent' => 1, 'required' => 1, 'performance' => 1, 'targeting' => 1]));
        $this->assertTrue(has_consent('performance'));

        $response = $this->post('consent', ['choice' => 'custom', 'consents' => ['targeting' => '1']]);
        service('superglobals')->setCookie('bf_consent', $response->response()->getCookie('bf_consent')->getValue());

        $this->assertFalse(has_consent('performance'));
        $this->assertTrue(has_consent('targeting'));
    }

    public function testEachSaveAppendsARecordUnderTheSameConsentId(): void
    {
        $first = $this->post('consent', ['choice' => 'accept']);
        $id    = json_decode($first->response()->getCookie('bf_consent')->getValue(), true)['consent_id'];
        service('superglobals')->setCookie('bf_consent', $first->response()->getCookie('bf_consent')->getValue());

        $this->post('consent', ['choice' => 'reject']);

        $records = db_connect()->table('consent_records')->orderBy('id')->get()->getResultArray();
        $this->assertCount(2, $records);
        $this->assertSame([$id, $id], array_column($records, 'consent_id'));
        $this->assertSame(['required' => 1, 'performance' => 0, 'targeting' => 0], json_decode($records[1]['consents'], true));
        $this->assertNull($records[0]['user_id']);
        $this->assertNotEmpty($records[0]['created_at']);
    }

    public function testRecordsTheSignedInUser(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)->post('consent', ['choice' => 'accept']);

        $this->seeInDatabase('consent_records', ['user_id' => $user->id]);
    }

    public function testStoresNoIpAddressOrUserAgent(): void
    {
        $this->assertSame(
            ['id', 'consent_id', 'user_id', 'consents', 'created_at'],
            db_connect()->getFieldNames('consent_records'),
        );
    }

    /**
     * @return array<string, int>
     */
    private function choices(TestResponse $response): array
    {
        $values = json_decode($response->response()->getCookie('bf_consent')->getValue(), true);

        $this->assertSame(1, $values['consent']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $values['consent_id']);

        return array_diff_key($values, ['consent' => true, 'consent_id' => true]);
    }
}
