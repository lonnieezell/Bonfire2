<?php

namespace Tests\Consent;

use CodeIgniter\Config\Factories;
use PHPUnit\Framework\Attributes\BackupGlobals;
use Tests\Support\TestCase;

/**
 * @internal
 */
#[BackupGlobals(false)]
final class ConsentHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! function_exists('has_consent')) {
            helper('consent');
        }
        if (! function_exists('setting')) {
            helper('setting');
        }
        service('superglobals')->setCookieArray([]);

        $config                 = config('Consent');
        $config->requireConsent = true;
        Factories::injectMock('config', 'Consent', $config);
    }

    public function testGroupNotGiven()
    {
        service('superglobals')->setCookie('bf_consent', json_encode([
            'consent'  => 1,
            'required' => 1,
            'ads'      => 0,
        ]));

        $this->assertTrue(has_consent('required'));
        $this->assertFalse(has_consent('ads'));
    }

    public function testGroupNotExist()
    {
        service('superglobals')->setCookie('bf_consent', json_encode([
            'consent'  => 1,
            'required' => 1,
            'ads'      => 0,
        ]));

        $this->assertFalse(has_consent('foo'));
    }

    public function testDefaultNoConsent()
    {
        $this->assertFalse(has_consent('foo'));
    }
}
