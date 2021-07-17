<?php

namespace Tests\Bonfire\Models;

use Bonfire\Models\ConfigModel;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Config\Factories;
use Tests\Support\TestCase;

class ConfigModelTest extends TestCase
{
    use DatabaseTestTrait;

    public function testHydrateCachesValues()
    {
        $model = new ConfigModel();
        $table = config('Config')->configTable;
        $date = date('Y-m-d H:i:z');
        $this->hasInDatabase($table, ['class' => 'Foo', 'key' => 'bar', 'value' => 'baz', 'created_at' => $date, 'updated_at' => $date]);
        $this->hasInDatabase($table, ['class' => 'Foo\Bar', 'key' => 'raz', 'value' => 'taz', 'created_at' => $date, 'updated_at' => $date]);

        $model->hydrate();

        $cache = $this->getPrivateProperty($model, 'cache');
        $this->assertTrue(isset($cache['Foo']['bar']) && $cache['Foo']['bar'] === 'baz');
        $this->assertTrue(isset($cache['Foo\Bar']['raz']) && $cache['Foo\Bar']['raz'] === 'taz');
    }

    public function testPrepareSingleNothingSavedYet()
    {
        $model = new ConfigModel();
        $config = new \Config\Config();
        $config->configTable = 'foo';

        $method = $this->getPrivateMethodInvoker($model, 'prepareSingle');
        $method($config);

        $class = get_class($config);
        $dirty = $this->getPrivateProperty($model, 'dirty');
        $this->assertCount(1, $dirty);
        $this->assertEquals($dirty[0], [
            'class' => $class,
            'key' => 'configTable',
            'value' => 'foo'
        ]);
    }

    public function testPrepareSingleSavedNotChanged()
    {
        $model = new ConfigModel();
        $config = new \Config\Config();
        $config->configTable = 'foo';

        $model->insert([
                           'class' => get_class($config),
                           'key' => 'configTable',
                           'value' => 'foo'
                       ]);

        $model->hydrate();
        $method = $this->getPrivateMethodInvoker($model, 'prepareSingle');
        $method($config);

        $dirty = $this->getPrivateProperty($model, 'dirty');
        // Shouldn't resave values that are the same as what is in the database.
        $this->assertCount(0, $dirty);
    }

    public function testPersistNoChanges()
    {
        $table = config('Config')->configTable;
        $model = new ConfigModel();
        $config = new \Config\Config();
        Factories::injectMock('config', 'config', $config);

        $model->persist();

        $this->dontSeeInDatabase($table, [
            'class' => get_class($config),
            'key' => 'configTable',
        ]);
    }

    public function testPersistInsertsNewRows()
    {
        $table = config('Config')->configTable;
        $model = new ConfigModel();
        $config = new \Config\Config();
        $config->configTable = 'foo';
        Factories::injectMock('config', 'config', $config);

        $result = $model->persist();

        // Depending on your current environment, it may have
        // more than one config file setting to save.
        $this->assertTrue(isset($result['inserted']) && $result['inserted'] > 0);
        // So make sure the one we expected did get saved.
        $this->seeInDatabase($table, [
            'class' => get_class($config),
            'key' => 'configTable',
            'value' => 'foo'
        ]);
    }

    public function testPersistWithBoolAndArray()
    {
        $table = config('Config')->configTable;
        $model = new ConfigModel();
        $config = new \Config\Config();
        $config->configTable = true;
        $config->persistConfig = ['foo' => 'bar'];
        Factories::injectMock('config', 'config', $config);

        $result = $model->persist();

        // Depending on your current environment, it may have
        // more than one config file setting to save.
        $this->assertTrue(isset($result['inserted']) && $result['inserted'] > 0);
        // So make sure the one we expected did get saved.
        $this->seeInDatabase($table, [
            'class' => get_class($config),
            'key' => 'configTable',
            'value' => 1,
        ]);
        $this->seeInDatabase($table, [
            'class' => get_class($config),
            'key' => 'persistConfig',
            'value' => serialize(['foo' => 'bar']),
        ]);
    }

    public function testPersistWithUpdates()
    {
        $table = config('Config')->configTable;
        $model = new ConfigModel();
        $config = new \Config\Config();
        $config->configTable = 'bar';
        Factories::injectMock('config', 'config', $config);

        $this->hasInDatabase($table, [
            'class' => get_class($config),
            'key' => 'configTable',
            'value' => 'foo',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $result = $model->persist();

        // Depending on your current environment, it may have
        // more than one config file setting to save.
        $this->assertTrue(isset($result['updated']) && $result['updated'] > 0);
        // So make sure the one we expected did get saved.
        $this->seeInDatabase($table, [
            'class' => get_class($config),
            'key' => 'configTable',
            'value' => 'bar'
        ]);
    }
}
