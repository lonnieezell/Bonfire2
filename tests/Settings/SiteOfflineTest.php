<?php

namespace Tests\Settings;

use Bonfire\Users\User;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class SiteOfflineTest extends TestCase
{
    protected $refresh = true;
    private User $admin;
    private User $nonAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createUser();
        $this->admin->addGroup('superadmin');
        $this->nonAdmin = $this->createUser();

        helper('setting');
    }

    public function testOnlySuperAdminWhenOff()
    {
        setting('Site.siteOnline', null);

        // Superadmin should be able to see the site
        $this->actingAs($this->admin)
            ->get(ADMIN_AREA)
            ->assertOK();

        // Other users should NOT be able to see the site
        $this->actingAs($this->nonAdmin)
            ->get(ADMIN_AREA)
            ->assertRedirectTo('site-offline');
    }

    public function testOffWhenTheSwitchPostsZero()
    {
        // The settings form's switch posts "0" when it is off.
        setting('Site.siteOnline', '0');

        $this->actingAs($this->nonAdmin)
            ->get(ADMIN_AREA)
            ->assertRedirectTo('site-offline');
    }

    public function testAllWhenOn()
    {
        setting('Site.siteOnline', true);

        // Superadmin should be able to see the site
        $this->actingAs($this->admin)
            ->get(ADMIN_AREA)
            ->assertOK();

        // Other users should be able to see the site
        $this->actingAs($this->nonAdmin)
            ->get(ADMIN_AREA)
            ->assertOk();
    }
}
