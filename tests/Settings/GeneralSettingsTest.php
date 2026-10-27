<?php

namespace Tests\Settings;

use Bonfire\Users\User;
use Tests\Support\SubmitsForms;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class GeneralSettingsTest extends TestCase
{
    use SubmitsForms;

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

    public function testEverySettingRoundTripsThroughTheForm(): void
    {
        setting('Site.siteName', 'Round Trip');
        setting('Site.siteOnline', '1');
        setting('App.appTimezone', 'Europe/Paris');
        setting('App.dateFormat', 'd.m.Y');
        setting('App.timeFormat', 'H:i');
        setting('Site.adminAccent', '#0F9F6E');

        $html   = $this->actingAs($this->user)->get('/admin/settings/general')->getBody();
        $fields = $this->formFields($html, site_url(ADMIN_AREA . '/settings/general'));

        $this->actingAs($this->user)->post('/admin/settings/general', $fields)->assertSessionMissing('errors');

        $this->assertSame('Round Trip', setting('Site.siteName'));
        $this->assertSame('1', setting('Site.siteOnline'));
        $this->assertSame('Europe/Paris', setting('App.appTimezone'));
        $this->assertSame('d.m.Y', setting('App.dateFormat'));
        $this->assertSame('H:i', setting('App.timeFormat'));
        $this->assertSame('#0F9F6E', setting('Site.adminAccent'));
    }

    public function testSwitchingTheSiteOffline(): void
    {
        setting('Site.siteOnline', '1');

        $html   = $this->actingAs($this->user)->get('/admin/settings/general')->getBody();
        $fields = $this->formFields($html, site_url(ADMIN_AREA . '/settings/general'));
        $this->assertSame('1', $fields['siteOnline']);

        $fields['siteOnline'] = '0'; // only the switch's hidden input posts when it is off
        $this->actingAs($this->user)->post('/admin/settings/general', $fields);

        $this->assertFalse((bool) setting('Site.siteOnline'));
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
