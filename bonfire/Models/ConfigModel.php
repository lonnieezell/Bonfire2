<?php

namespace Bonfire\Models;

use CodeIgniter\Model;
use CodeIgniter\Test\ReflectionHelper;
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
    use ReflectionHelper;

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
     * Given a config file, will update it's values
     * with the values store in the db, if any.
     *
     * @param BaseConfig $config
     */
    public function hydrateConfig(BaseConfig $config): BaseConfig
    {
        if (! $this->isHydrated) {
            $this->hydrate();
        }

        $className = get_class($config);

        if (! isset($this->cache[$className])) {
            return $config;
        }

        foreach($this->cache[$className] as $key => $value) {
            if($key === 'id') {
                continue;
            }

            if (property_exists($config, $key)) {
                $config->{$key} = $this->parseValue($value);
            }
        }

        return $config;
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
    private function prepareAll()
    {
        // Grab the 'config' instances from the Factories class
        // in an unsupported manner for now. A solution for this
        // will likely be added to core soon....
        $configInstances = $this->getPrivateProperty(Factories::class, 'instances');

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
    private function prepareSingle(BaseConfig $config)
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

            $this->dirty[] = [
                'class' => $class,
                'key' => $key,
                'value' => $this->prepareValue($config->{$key})
            ];
        }
    }

    /**
     * Takes care of converting some item types so they can be safely
     * stored and re-hydrated into the config files.
     *
     * @param $value
     *
     * @return string
     */
    private function prepareValue($value)
    {
        if ($value === true) {
            return ':true';
        }

        if ($value === false) {
            return ':false';
        }

        if (is_array($value) || is_object($value)) {
            return serialize($value);
        }

        return $value;
    }

    /**
     * Handles some special case conversions that
     * data might have been saved as, such as booleans
     * and serialized data.
     *
     * @param $value
     *
     * @return bool|mixed
     */
    private function parseValue($value)
    {
        // :true -> boolean
        if ($value === ':true') {
            return true;
        }

        // :false -> boolean
        if ($value === ':false') {
            return false;
        }

        // Serialized?
        if ($this->isSerialized($value)) {
            return unserialize($value);
        }

        return $value;
    }

    /**
     * Checks to see if an object is serialized and correctly formatted.
     *
     * Taken from Wordpress core functions.
     *
     * @param      $data
     * @param bool $strict  Whether to be strict about the end of the string.
     *
     * @return bool
     */
    private function isSerialized($data, $strict=true ): bool
    {
        // If it isn't a string, it isn't serialized.
        if ( ! is_string( $data ) ) {
            return false;
        }
        $data = trim( $data );
        if ( 'N;' === $data ) {
            return true;
        }
        if ( strlen( $data ) < 4 ) {
            return false;
        }
        if ( ':' !== $data[1] ) {
            return false;
        }
        if ( $strict ) {
            $lastc = substr( $data, -1 );
            if ( ';' !== $lastc && '}' !== $lastc ) {
                return false;
            }
        } else {
            $semicolon = strpos( $data, ';' );
            $brace     = strpos( $data, '}' );
            // Either ; or } must exist.
            if ( false === $semicolon && false === $brace ) {
                return false;
            }
            // But neither must be in the first X characters.
            if ( false !== $semicolon && $semicolon < 3 ) {
                return false;
            }
            if ( false !== $brace && $brace < 4 ) {
                return false;
            }
        }
        $token = $data[0];
        switch ( $token ) {
            case 's':
                if ( $strict ) {
                    if ( '"' !== substr( $data, -2, 1 ) ) {
                        return false;
                    }
                } elseif ( false === strpos( $data, '"' ) ) {
                    return false;
                }
            // Or else fall through.
            case 'a':
            case 'O':
                return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
            case 'b':
            case 'i':
            case 'd':
                $end = $strict ? '$' : '';
                return (bool) preg_match( "/^{$token}:[0-9.E+-]+;$end/", $data );
        }
        return false;
    }
}
