<?php

namespace Tests;

use Tests\Support\TestCase;

class SiteOnlineTest extends TestCase
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
        setting('Site.siteOnline', false);
        $response = $this->get('/');

        $response->assertRedirectTo('site-offline');
    }

    public function testSiteOfflineSuperAdmin()
    {
        setting('Site.siteOnline', false);

        $user = $this->createUser();
        $user->addGroup('superadmin');

        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);
        // Should display the CI welcome page.
        $response->assertSee('Welcome to CodeIgniter');
    }
}
