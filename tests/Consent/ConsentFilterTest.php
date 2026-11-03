<?php

declare(strict_types=1);

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Consent;

use Bonfire\Consent\Filters\ConsentFilter;
use CodeIgniter\Config\Services;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class ConsentFilterTest extends TestCase
{
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();

        service('superglobals')->setCookieArray([]);
    }

    protected function tearDown(): void
    {
        Services::resetSingle('settings');

        parent::tearDown();
    }

    public function testResponsesWithoutABodyTagAreLeftAlone(): void
    {
        setting('Consent.requireConsent', true);

        foreach (['{"ok":true}', '<div id="partial">htmx</div>', ''] as $body) {
            $response = service('response')->setBody($body);

            $this->assertNull((new ConsentFilter())->after(service('request'), $response));
            $this->assertSame($body, $response->getBody());
        }
    }

    public function testNothingIsAddedWhileConsentIsOff(): void
    {
        setting('Consent.requireConsent', false);

        $body     = '<html><head></head><body></body></html>';
        $response = service('response')->setBody($body);

        $this->assertNull((new ConsentFilter())->after(service('request'), $response));
        $this->assertSame($body, $response->getBody());
    }

    public function testTheFormGoesInsideTheBodyOfAFullPage(): void
    {
        setting('Consent.requireConsent', true);

        $response = service('response')->setBody('<html><head></head><body><p>Hi</p></body></html>');
        $result   = (new ConsentFilter())->after(service('request'), $response);

        $this->assertNotNull($result);
        $this->assertMatchesRegularExpression('#<p>Hi</p>.*id="consent-popup".*</body>#s', $result->getBody());
    }
}
