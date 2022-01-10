<?php

namespace Tests\Bonfire\Users;

use Bonfire\Modules\Users\SearchProvider;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class SearchProviderTest extends TestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace;

    /**
     * @var SearchProvider
     */
    protected $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new SearchProvider();
    }

    public function testSearchNoUsers()
    {
        $result = $this->provider->search('foo');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testSearchMatchFirstName()
    {
        $this->createUser(['first_name' => 'John', 'last_name' => 'Stone', 'email' => 'john.stone@example.com']);
        $this->createUser(['first_name' => 'Fred', 'last_name' => 'Stone', 'email' => 'fred.stone@example.com']);

        $result = $this->provider->search('fre');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    public function testSearchMatchLastName()
    {
        $this->createUser(['first_name' => 'John', 'last_name' => 'Stone', 'email' => 'john.stone@example.com']);
        $this->createUser(['first_name' => 'Fred', 'last_name' => 'Rogers', 'email' => 'fred@example.com']);

        $result = $this->provider->search('rog');

        $this->assertCount(1, $result);
        $this->assertSame('Rogers', $result[0]->last_name);
    }

    public function testSearchMatchEmail()
    {
        $this->createUser(['first_name' => 'John', 'last_name' => 'Stone', 'email' => 'john.stone@example.com']);
        $this->createUser(['first_name' => 'Fred', 'last_name' => 'Stone', 'email' => 'fred.stone@example.com']);

        $result = $this->provider->search('exam');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    public function testResourceUrl()
    {
        $this->assertSame('admin/users', $this->provider->resourceUrl());
    }
}
