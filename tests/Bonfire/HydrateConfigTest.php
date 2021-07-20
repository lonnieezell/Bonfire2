<?php

namespace Tests\Bonfire;

use CodeIgniter\Test\DatabaseTestTrait;
use Config\Cache;
use Config\Config;
use Config\Database;
use Tests\Support\TestCase;

class HydrateConfigTest extends TestCase
{
    use DatabaseTestTrait;

    public function testHydratesFromDatabase()
    {
        $config = new \Config\Config();
        $table = $config->configTable;

        $cache = new Cache();

        $this->hasInDatabase($table, [
            'class' => get_class($cache),
            'key' => 'handler',
            'value' => 'foo',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->hasInDatabase($table, [
            'class' => get_class($cache),
            'key' => 'cacheQueryString',
            'value' => ':true',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $newConfig = config('Cache');
        $newConfig->hydrate();

        $this->assertEquals('foo', $newConfig->handler);
        $this->assertEquals(true, $newConfig->cacheQueryString);
    }
}
