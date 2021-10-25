<?php

namespace Tests\Bonfire\Settings;

use App\Entities\User;
use Tests\Support\TestCase;

class SiteOfflineTest extends TestCase
{
    protected $refresh = true;
    protected $namespace = null;

    /**
     * @var User
     */
    protected $admin;

    /**
     * @var User
     */
    protected $nonAdmin;

    public function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createUser();
        $this->admin->addGroup('superadmin');
        $this->nonAdmin = $this->createUser();

        helper('setting');
    }

    public function testOnlySuperAdminWhenOff()
    {
        setting('App.siteOnline', false);

        // Superadmin should be able to see the site
        $this->actingAs($this->admin)
            ->get(ADMIN_AREA)
            ->assertOK();

        // Other users should NOT be able to see the site
        $this->actingAs($this->nonAdmin)
             ->get(ADMIN_AREA)
             ->assertRedirectTo('site-offline');
    }

    public function testAllWhenOn()
    {
        setting('App.siteOnline', true);

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
