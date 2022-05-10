<?php

namespace Tests\Dashboard;

use Tests\Support\TestCase;
use Bonfire\Users\User;

class DashboardTest extends TestCase
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

    public function testCanSeeDashboard()
    {
        $response = $this->actingAs($this->user)
            ->get(ADMIN_AREA);

        $response->assertOK();
        $response->assertSee('Home sweet home');
    }
}
