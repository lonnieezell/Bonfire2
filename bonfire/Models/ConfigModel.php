<?php

namespace Bonfire\Models;

use CodeIgniter\Model;
use ReflectionClass;
use ReflectionProperty;
use CodeIgniter\Config\Factories;
use CodeIgniter\Config\BaseConfig;

/**
 * Class ConfigModel
 *
 * Provides the ability to persist and load the config settings
 * for config files to and from the database.
 *
 * @package CodeIgniter\Config
 */
class ConfigModel extends Model
{
    protected $table = 'ci_config';
    protected $allowedFields = ['class', 'key', 'value'];
    protected $useTimestamps = true;
    protected $returnType = 'object';

    /**
     * Stores the values retrieved from the database
     * for all config classes. We only want to hit the
     * database once so we store them here.
     *
     * @var array
     */
    protected $cache = [];

    /**
     * @var bool
     */
    protected $isHydrated = false;

    /**
     * Stores class/key/value info found
     * from config values that are different
     * than the defaults and need to be saved.
     * Stored here until ready to save all at once.
     *
     * @var array
     */
    protected $dirty = [];

    public function __construct(...$params)
    {
        parent::__construct(...$params);

        $this->table = config('Config')->configTable;
    }

    /**
     * Retrieves all records from the database and formats
     * them to save conveniently to for later lookup.
     *
     * @return ConfigModel
     */
    public function hydrate(): ConfigModel
    {
        $values = $this->findAll();

        // Store them in the cache based on the class name for faster lookup
        foreach($values as $row) {
            if (! array_key_exists($row->class, $this->cache)) {
                $this->cache[$row->class] = [];
            }

            $this->cache[$row->class]['id'] = $row->id;
            $this->cache[$row->class][$row->key] = $row->value;
        }

        $this->isHydrated = true;

        return $this;
    }

    /**
     * When called, will examine all config files it can find
     * and check each public property, saving any that have
     * changed from the default value to the database.
     *
     * @todo Translate true/false values to ':true', ':false' so they can be converted back correctly
     */
    public function persist()
    {
        if (! $this->isHydrated) {
            $this->hydrate();
        }

        $this->prepareAll();

        if (empty($this->dirty)) {
            return;
        }

        $dirty = $this->dirty;

        // Perform updates on any dirty items already in the database
        $updates = [];
        foreach($dirty as $index => $item) {
            // Did the key already exist in the db?
            if (isset($this->cache[$item['class']][$item['key']])) {
                // If it's the same, we can ignore it
                if ($this->cache[$item['class']][$item['key']] == $item['value']) {
                    continue;
                }

                $updates[] = array_merge(['id' => $this->cache[$item['class']]['id']], $item);
                unset($dirty[$index]);
            }
        }

        if (count($updates)) {
            $updateCount = $this->updateBatch($updates, 'id');
        }

        // Insert the rest
        if (count($dirty)) {
            $insertCount = $this->insertBatch($dirty);
        }

        return [
            'inserted' => $insertCount ?? 0,
            'updated' => $updateCount ?? 0
        ];
    }

    /**
     * Scans all config files, collecting the changed
     * values into $this->dirty, so the values can be saved.
     * Will only examine config files that have been loaded
     * during this request and cached by Config\Factories.
     */
    protected function prepareAll()
    {
        $configInstances = Factories::getInstances();

        if (empty($configInstances['config'])) {
            return;
        }

        foreach($configInstances['config'] as $config) {
            if (! $config instanceof BaseConfig) {
                continue;
            }

            $this->prepareSingle($config);
        }
    }

    /**
     * Examines a config class to see if any properties
     * are different than the default value and need to be saved.
     *
     * @param BaseConfig $config
     */
    protected function prepareSingle(BaseConfig $config)
    {
        $class = get_class($config);
        $defaults = get_class_vars($class);

        // A few items we never want to update
        $dontPersist = ['registrars'];

        // Add any properties that have changed from the default
        // to $this->dirty so they can be saved later.
        foreach($defaults as $key => $value) {
            if(in_array($key, $dontPersist) || $config->{$key} === $value) {
                continue;
            }

            // If it hasn't changed from the value in the db, no need to do anything
            if (isset($this->cache[$class][$key]) && $this->cache[$class][$key] === $config->{$key}) {
                continue;
            }

            // Handle array/object values to save
            $set = $config->{$key};
            if (is_array($set) || is_object($set)) {
                $set = serialize($set);
            }

            $this->dirty[] = [
                'class' => $class,
                'key' => $key,
                'value' => $set
            ];
        }
    }
}
