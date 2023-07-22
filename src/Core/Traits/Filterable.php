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
        array_walk($filters, function (&$item, $key) {
            if (! is_string($item['options'])) {
                return;
            }

            $item['options'] = $this->{$item['options']}();
        });

        return $filters;
    }

    /**
     * @return $this
     */
    public function setFilters(array $filters)
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * A simple version of filtering that will take all
     * params passed in, assuming that they are columns in
     * the database and values. All searches are done as
     * whereIn.
     */
    public function filter(?array $params = null)
    {
        if (is_array($params)) {
            foreach ($params as $key => $values) {
                if (! is_array($values)) {
                    $values = [$values];
                }
                $this->whereIn($key, $values);
            }
        }

        return $this;
    }
}
