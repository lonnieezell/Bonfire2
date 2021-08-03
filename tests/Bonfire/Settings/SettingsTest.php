<?php

namespace Tests\Bonfire\Settings;

use Bonfire\Settings\Settings;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\TestCase;

class SettingsTest extends TestCase
{
    use DatabaseTestTrait;

    protected $namespace = 'App';
    protected $table;

    public function setUp(): void
    {
        parent::setUp();

        $this->table = config('Settings')->database['table'];
    }

    public function testSettingsGetsFromConfig()
    {
        $settings = new Settings();

        $this->assertEquals(config('App')->siteName, $settings->get('App', 'siteName'));
    }

    public function testSettingsDatabaseNotFound()
    {
        $settings = new Settings();

        $this->assertEquals(config('App')->siteName, $settings->get('App', 'siteName'));
    }

    public function testSetInsertsNewRows()
    {
        $settings = new Settings();

        $results = $settings->set('App', 'siteName', 'Foo');

        $this->assertTrue($results);
        $this->seeInDatabase($this->table, [
            'class' => 'Config\App',
            'key' => 'siteName',
            'value' => 'Foo'
        ]);
    }

    public function testSetInsertsBoolTrue()
    {
        $settings = new Settings();

        $results = $settings->set('App', 'siteName', true);

        $this->assertTrue($results);
        $this->seeInDatabase($this->table, [
            'class' => 'Config\App',
            'key' => 'siteName',
            'value' => ':true'
        ]);

        $this->assertEquals(true, $settings->get('App', 'siteName'));
    }

    public function testSetInsertsBoolFalse()
    {
        $settings = new Settings();

        $results = $settings->set('App', 'siteName', false);

        $this->assertTrue($results);
        $this->seeInDatabase($this->table, [
            'class' => 'Config\App',
            'key' => 'siteName',
            'value' => ':false'
        ]);

        $this->assertEquals(false, $settings->get('App', 'siteName'));
    }

    public function testSetInsertsArray()
    {
        $settings = new Settings();
        $data = ['foo' => 'bar'];

        $results = $settings->set('App', 'siteName', $data);

        $this->assertTrue($results);
        $this->seeInDatabase($this->table, [
            'class' => 'Config\App',
            'key' => 'siteName',
            'value' => serialize($data)
        ]);

        $this->assertEquals($data, $settings->get('App', 'siteName'));
    }

    public function testSetInsertsObject()
    {
        $settings = new Settings();
        $data = (object)['foo' => 'bar'];

        $results = $settings->set('App', 'siteName', $data);

        $this->assertTrue($results);
        $this->seeInDatabase($this->table, [
            'class' => 'Config\App',
            'key' => 'siteName',
            'value' => serialize($data)
        ]);

        $this->assertEquals($data, $settings->get('App', 'siteName'));
    }

    public function testSetUpdatesExistingRows()
    {
        $settings = new Settings();

        $this->hasInDatabase($this->table, [
            'class' => 'Config\App',
            'key' => 'siteName',
            'value' => 'foo',
            'created_at' => Time::now()->toDateTimeString(),
            'updated_at' => Time::now()->toDateTimeString(),
        ]);

        $results = $settings->set('App', 'siteName', 'Bar');

        $this->assertTrue($results);
        $this->seeInDatabase($this->table, [
            'class' => 'Config\App',
            'key' => 'siteName',
            'value' => 'Bar'
        ]);
    }
}
