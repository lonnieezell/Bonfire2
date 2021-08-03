<?php

namespace Bonfire\Settings;

use CodeIgniter\I18n\Time;

/**
 * Provides database storage for Settings.
 */
class DatabaseHandler extends BaseHandler
{
    /**
     * Stores our cached settings retrieved
     * from the database on the first get() call
     * to reduce the number of database calls
     * at the expense of a little bit of memory.
     *
     * @var array
     */
    private $settings = [];

    /**
     * Have the settings been read and cached
     * from the database yet?
     *
     * @var bool
     */
    private $hydrated = false;

    /**
     * The settings table
     * @var string
     */
    private $table;

    /**
     * Attempt to retrieve a value from the database.
     * To boost performance, all of the values are
     * read and stored in $this->settings the first
     * time, and then used from there the rest of the request.
     *
     * @param string $class
     * @param string $key
     *
     * @return mixed|null
     */
    public function get(string $class, string $key)
    {
        $this->hydrate();

        if (! isset($this->settings[$class]) || ! isset($this->settings[$class][$key])) {
            return null;
        }

        return $this->parseValue($this->settings[$class][$key]);
    }

    /**
     * Stores values into the database for later retrieval.
     *
     * @param string $class
     * @param string $key
     * @param null   $value
     *
     * @return mixed|void
     */
    public function set(string $class, string $key, $value=null)
    {
        $this->hydrate();
        $time = Time::now()->format('Y-m-d H:i:s');
        $value = $this->prepareValue($value);

        // If we found it in our cache, then we need to update
        if (isset($this->settings[$class][$key])) {
            $result = db_connect()->table($this->table)
                ->where('class', $class)
                ->where('key', $key)
                ->update([
                    'value' => $value,
                    'updated_at' => $time,
                ]);
        }
        else {
            $result = db_connect()->table($this->table)
               ->insert([
                    'class'      => $class,
                    'key'        => $key,
                    'value'      => $value,
                    'created_at' => $time,
                    'updated_at' => $time
                ]);
        }

        // Update our cache
        if ($result === true) {
            $this->settings[$class][$key] = $value;
        }

        return $result;
    }

    /**
     * Ensures we've pulled all of the values from the database.
     */
    private function hydrate()
    {
        if ($this->hydrated) {
            return;
        }

        $this->table = config('Settings')->database['table'] ?? 'settings';

        $rawValues = db_connect()->table($this->table)->get()->getResultObject();

        foreach($rawValues as $row) {
            if (! array_key_exists($row->class, $this->settings)) {
                $this->settings[$row->class] = [];
            }

            $this->settings[$row->class][$row->key] = $row->value;
        }

        $this->hydrated = true;
    }
}
