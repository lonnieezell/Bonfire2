<?php

namespace Bonfire\Traits;

use Bonfire\Models\ConfigModel;

trait HydrateConfig
{
    protected $didHydrate = false;

    /**
     * Loads any config settings from the database.
     *
     * @return $this
     */
    public function hydrate()
    {
        if (! $this->didHydrate) {
            model(ConfigModel::class)->hydrateConfig($this);

            $this->didHydrate = true;
        }

        return $this;
    }

    /**
     * A short-hand to ensure the config file is hydrated
     * with database values before returning the requested property.
     *
     * @param string $property
     *
     * @return mixed
     */
    public function get(string $property)
    {
        $this->hydrate();

        return $this->{$property};
    }
}
