<?php

namespace Tests\Bonfire\Resources;

use App\Models\UserModel;
use Bonfire\Resources\ResourceTab;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\TestCase;

class ResourceTabTest extends TestCase
{
    use DatabaseTestTrait;
    protected $namespace = null;

    public function tearDown(): void
    {
        parent::tearDown();

        $_SERVER = [];
    }

    public function testBasicCreation()
    {
        $tab = new ResourceTab([
            'title' => 'Resource A',
            'url' => 'users/gallery',
            'permission' => 'users.edit'
        ]);

        $this->assertEquals('Resource A', $tab->title);
        $this->assertEquals(site_url(ADMIN_AREA .'/users/gallery'), $tab->url);
        $this->assertEquals('users.edit', $tab->permission);
        $this->assertNull($tab->foo);
    }

    public function testBasicWithEmptyUrl()
    {
        $tab = new ResourceTab();

        $this->assertEquals('', $tab->url);
    }
}
