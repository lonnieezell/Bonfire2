<?php

namespace Tests;

use Tests\Support\TestCase;

/**
 * @internal
 */
final class SiteOnlineTest extends TestCase
{
    public function testSiteOnline()
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        // Should display the CI welcome page.
        $response->assertSee('Welcome to CodeIgniter');
    }

    public function testSiteOffline()
    {
        setting('Site.siteOnline', null);
        $response = $this->get('/');

        $response->assertRedirectTo('site-offline');
    }

    public function testSiteOfflineSuperAdmin()
    {
        setting('Site.siteOnline', null);

        $user = $this->createUser();
        $user->addGroup('superadmin');

        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);
        // Should display the CI welcome page.
        $response->assertSee('Welcome to CodeIgniter');
    }
}
