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

trait SearchInMeta
{
    /**
     * Custom method to join the meta_info table.
     *
     * @param string $className The resource entity class namespaced name that the
     *                          data is associated with in meta_info table.
     * @param string $tableName The name of the table to join meta_info to.
     *
     * @return $this
     */
    public function joinMetaInfo($className, $tableName)
    {
        $this->join('meta_info m', $tableName . '.id = m.resource_id AND m.class = "' . $className . '"', 'left');

        return $this;
    }

    /**
     * Custom method to do a like query on meta_info table during search.
     *
     * @param string    $field             The key to search for.
     * @param mixed     $match             The value to search for.
     * @param string    $side              The side to add the wildcard ('both', 'left', 'right', or 'none').
     * @param bool|null $escape            Whether to escape the value.
     * @param bool      $insensitiveSearch Whether to perform a case-insensitive search.
     *
     * @return $this
     */
    public function orLikeInMetaInfo($field, $match, $side = 'both', $escape = null, $insensitiveSearch = false)
    {
        $this->orGroupStart()
            ->where('m.key', $field)
            ->like('m.value', $match, $side, $escape, $insensitiveSearch)
            ->groupEnd();

        return $this;
    }
}
