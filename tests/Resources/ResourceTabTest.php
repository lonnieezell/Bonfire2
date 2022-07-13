<?php

namespace Tests\Bonfire\Resources;

use Bonfire\Resources\ResourceTab;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class ResourceTabTest extends TestCase
{
    use DatabaseTestTrait;

    protected $namespace;

    protected function tearDown(): void
    {
        parent::tearDown();

        $_SERVER = [];
    }

    public function testBasicCreation()
    {
        $tab = new ResourceTab([
            'title'      => 'Resource A',
            'url'        => 'users/gallery',
            'permission' => 'users.edit',
        ]);

        $this->assertSame('Resource A', $tab->title);
        $this->assertSame(site_url(ADMIN_AREA . '/users/gallery'), $tab->url);
        $this->assertSame('users.edit', $tab->permission);
        $this->assertNull($tab->foo); // @phpstan-ignore-line
    }

    public function testBasicWithEmptyUrl()
    {
        $tab = new ResourceTab();

        $this->assertSame('', $tab->url);
    }
}
