<?php

namespace Tests\Bonfire\Users;

use App\Entities\User;
use CodeIgniter\Config\Factories;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class MetaTest extends TestCase
{
    use DatabaseTestTrait;

    protected $namespace;

    /**
     * @var User
     */
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser();

        // Ensure we have some fields to test against.
        $config             = config('Users');
        $config->metaFields = [
            'Example Fields' => [
                'foo' => ['label' => 'Foo', 'type' => 'text', 'required' => true, 'validation' => 'permit_empty|string'],
                'Bar' => ['type' => 'text', 'required' => true, 'validation' => 'required|string'],
            ],
        ];
        Factories::injectMock('config', 'Users', $config);
    }

    public function testSaveMeta()
    {
        $this->assertFalse($this->user->hasMeta('foo'));
        $this->assertNull($this->user->meta('bar'));

        $this->user->saveMeta('foo', 'Some great info here');

        $this->assertTrue($this->user->hasMeta('foo'));
        $this->assertSame('Some great info here', $this->user->meta('foo'));

        $this->seeInDatabase('meta_info', [
            'class'       => User::class,
            'resource_id' => $this->user->id,
            'key'         => 'foo',
            'value'       => 'Some great info here',
        ]);
    }

    public function testDeleteMeta()
    {
        // Setup
        $this->user->saveMeta('foo', 'Some great info here');
        $this->assertTrue($this->user->hasMeta('foo'));

        // Teardown
        $this->user->deleteMeta('foo');
        $this->assertFalse($this->user->hasMeta('foo'));
        $this->dontSeeInDatabase('meta_info', [
            'class'       => User::class,
            'resource_id' => $this->user->id,
            'key'         => 'foo',
        ]);

        // Shouldn't crash when key doesn't exist
        $this->user->deleteMeta('abcdefg');
    }

    public function testSyncMeta()
    {
        $this->assertFalse($this->user->hasMeta('foo'));

        // Insert new value from empty state
        $this->user->syncMeta(['foo' => 'aaa']);

        $this->assertTrue($this->user->hasMeta('foo'));
        $this->assertSame('aaa', $this->user->meta('foo'));

        // Update the value
        $this->user->syncMeta(['foo' => 'bbb']);
        $this->assertTrue($this->user->hasMeta('foo'));
        $this->assertSame('bbb', $this->user->meta('foo'));

        // Delete the value with empty string
        $this->user->syncMeta(['foo' => '']);
        $this->assertFalse($this->user->hasMeta('foo'));
    }

    public function testSyncMetaWithNull()
    {
        $this->assertFalse($this->user->hasMeta('foo'));

        // Insert new value from empty state
        $this->user->syncMeta(['foo' => 'aaa']);

        // Delete the value with NULL
        $this->user->syncMeta(['foo' => null]);
        $this->assertFalse($this->user->hasMeta('foo'));
    }
}
