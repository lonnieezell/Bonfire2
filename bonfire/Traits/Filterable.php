<?php

namespace Bonfire\Traits;

/**
 * Provides functionality for making resource filters workable.
 */
trait Filterable
{
    /**
     * Takes $this->filters and populates
     * any computed options with their final values.
     *
     * @return array
     */
    public function getFilters()
    {
        $filters = $this->filters;

        // Replace an options with computed values
        array_walk($filters, function(&$item, $key) {
            if (! is_string($item['options'])) {
                return;
            }

            $item['options'] = $this->{$item['options']}();
        });

        return $filters;
    }
}
