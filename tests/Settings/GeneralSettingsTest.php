<?php

namespace Tests\Bonfire\Settings;

use Bonfire\Users\User;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class GeneralSettingsTest extends TestCase
{
    protected $refresh = true;
    protected $namespace;

    /**
     * @var User
     */
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser();
        $this->user->addGroup('superadmin');
        helper('setting');
    }

    public function testSaveSettings()
    {
        setting('Site.siteName', 'My Site');
        setting('Site.siteOnline', false);

        // View page
        $response = $this->actingAs($this->user)
            ->get('/admin/settings/general');

        $response->assertOK();
        $response->assertSee('Settings');
        $response->assertSee('General');

        $response = $this->actingAs($this->user)
            ->post('/admin/settings/general', [
                'siteName'   => 'My Great Site',
                'siteOnline' => '1',
                'timezone'   => 'America/Los_Angeles',
                'dateFormat' => 'm/d/Y',
                'timeFormat' => 'g:i A',
            ]);

        $response->assertRedirect();

        $this->assertSame('My Great Site', setting('Site.siteName'));
        $this->assertTrue(setting('Site.siteOnline'));
    }
}
