<?php

namespace Tests\Settings;

use Bonfire\Users\User;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class GeneralSettingsTest extends TestCase
{
    protected $refresh = true;
    private User $user;

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
        $this->assertSame('1', setting('Site.siteOnline'));
    }

    public function testSavesAdminAccent()
    {
        $response = $this->actingAs($this->user)
            ->post('/admin/settings/general', [
                'siteName'    => 'My Site',
                'timezone'    => 'UTC',
                'dateFormat'  => 'm/d/Y',
                'timeFormat'  => 'g:i A',
                'adminAccent' => '#2F6FEB',
            ]);

        $response->assertRedirect();
        $this->assertSame('#2F6FEB', setting('Site.adminAccent'));
    }

    public function testRejectsInvalidAdminAccent()
    {
        setting('Site.adminAccent', '#0F9F6E');

        $response = $this->actingAs($this->user)
            ->post('/admin/settings/general', [
                'siteName'    => 'My Site',
                'timezone'    => 'UTC',
                'dateFormat'  => 'm/d/Y',
                'timeFormat'  => 'g:i A',
                'adminAccent' => 'red;}body{display:none',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('errors');
        $this->assertSame('#0F9F6E', setting('Site.adminAccent'));
    }

    public function testAdminPagesUseTheAccent()
    {
        setting('Site.adminAccent', '#7C5CE0');

        $response = $this->actingAs($this->user)
            ->get('/admin/settings/general');

        $response->assertOK();
        $response->assertSee('--bf-accent: #7C5CE0', 'style');
    }
}
