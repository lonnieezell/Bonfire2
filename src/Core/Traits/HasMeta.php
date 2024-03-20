<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Core\Traits;

use Bonfire\Core\Models\MetaModel;

/**
 * Provides "meta-field" support to Entities.
 * This allows storing user-configurable bits of information
 * for other classes without the need to modify those
 * classes.
 *
 * NOTE: In order to work this assumes the Entity being
 * used on has a primary key called 'id'.
 */
trait HasMeta
{
    /**
     * Cache for meta info
     *
     * @var array
     */
    private $meta = [];

    /**
     * Have we already hyrdated out meta info?
     *
     * @var bool
     */
    private $metaHydrated = false;

    /**
     * Grabs the meta data for this entity.
     */
    protected function hydrateMeta(bool $refresh = false)
    {
        if ($this->metaHydrated && ! $refresh) {
            return;
        }

        if ($refresh) {
            $this->meta = [];
        }

        $meta = model(MetaModel::class)
            ->where('class', static::class)
            ->where('resource_id', $this->id)
            ->findAll();

        // Index the array by key
        foreach ($meta as $row) {
            $this->meta[$row->key] = $row;
        }

        $this->metaHydrated = true;
    }

    /**
     * Returns the value of the request $key
     *
     * @return mixed|null
     */
    public function meta(string $key)
    {
        $this->hydrateMeta();

        $key = strtolower($key);

        return isset($this->meta[$key])
            ? $this->meta[strtolower($key)]->value
            : null;
    }

    /**
     * Returns all meta info for this entity.
     *
     * @return array
     */
    public function allMeta()
    {
        $this->hydrateMeta();

        return $this->meta;
    }

    /**
     * Does the user have this meta information?
     *
     * @return bool
     */
    public function hasMeta(string $key)
    {
        $this->hydrateMeta();

        return array_key_exists(strtolower($key), $this->meta);
    }

    /**
     * Saves the meta information for this entity.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function saveMeta(string $key, $value)
    {
        $this->hydrateMeta();
        $key   = strtolower($key);
        $model = model(MetaModel::class);

        // Update?
        if (array_key_exists($key, $this->meta)) {
            $result = $model
                ->where('class', static::class)
                ->where('resource_id', $this->id)
                ->where('key', $key)
                ->update(['value' => $value]);
        }

        // Insert
        else {
            $result = $model
                ->where('class', static::class)
                ->where('resource_id', $this->id)
                ->insert([
                    'class'       => static::class,
                    'resource_id' => $this->id,
                    'key'         => $key,
                    'value'       => $value,
                ]);
        }

        $this->meta[$key] = $model
            ->where('class', static::class)
            ->where('resource_id', $this->id)
            ->where('key', $key)
            ->first();

        return $result;
    }

    /**
     * Deletes a single meta value from this entity
     * and from the database.
     *
     * @return mixed
     */
    public function deleteMeta(string $key)
    {
        // Ensure it is initially populated
        $this->hydrateMeta();
        $key = strtolower($key);

        // Delete stuff
        $result = model(MetaModel::class)
            ->where('class', static::class)
            ->where('resource_id', $this->id)
            ->where('key', $key)
            ->delete();

        if ($result) {
            unset($this->meta[$key]);
        }

        return $result;
    }

    /**
     * Ensures our meta info for this resource is up
     * to date with what is given in $post. If a key
     * doesn't exist, it's deleted, otherwise it is
     * either inserted or updated.
     */
    public function syncMeta(array $post): void
    {
        $this->hydrateMeta();
        helper('setting');

        $inserts = [];
        $updates = [];
        $deletes = [];

        $metaInfo = setting("{$this->configClass}.metaFields");
        if (empty($metaInfo)) {
            return;
        }

        foreach ($metaInfo as $group => $fields) {
            if (! is_array($fields) || ! count($fields)) {
                continue;
            }

            foreach ($fields as $field => $info) {
                $field    = strtolower($field);
                $existing = array_key_exists($field, $this->meta);

                // Not existing and no value?
                if (! $existing && ! array_key_exists($field, $post)) {
                    continue;
                }

                // Create a new one
                if (! $existing && ! empty($post[$field])) {
                    $inserts[] = [
                        'resource_id' => $this->id,
                        'key'         => $field,
                        'value'       => $post[$field],
                        'class'       => static::class,
                    ];

                    continue;
                }

                // Existing one with no value now
                if ($existing && array_key_exists($field, $post) && empty($post[$field])) {
                    $deletes[] = $this->meta[$field]->id;

                    continue;
                }

                // Update existing one
                if ($existing) {
                    $updates[] = [
                        'id'    => $this->meta[$field]->id,
                        'key'   => $field,
                        'value' => $post[$field],
                    ];
                }
            }

            $model = model(MetaModel::class);
            if (count($deletes)) {
                $model->whereIn('id', $deletes)->delete();
            }

            if (count($inserts)) {
                $model->insertBatch($inserts);
            }

            if (count($updates)) {
                $model->updateBatch($updates, 'id');
            }

            $this->hydrateMeta(true);
        }
    }

    /**
     * Returns all the validation rules set
     * for the given entity
     *
     * @param string|null $prefix // Specifies the form array name, if any
     */
    public function metaValidationRules(?string $prefix = null): array
    {
        $rules = [];
        helper('setting');
        $metaInfo = setting("{$this->configClass}.metaFields");

        if (empty($metaInfo)) {
            return $rules;
        }

        foreach ($metaInfo as $group => $rows) {
            if (! count($rows)) {
                continue;
            }

            foreach ($rows as $name => $row) {
                $name = strtolower($name);
                if (! empty($prefix)) {
                    $name = "{$prefix}.{$name}";
                }

                if (isset($row['validation'])) {
                    $rules[$name] = $row['validation'];
                }
            }
        }

        return $rules;
    }
}
