<?php

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Modules\Search\Interfaces;

interface SearchProviderInterface
{
    /**
     * Performs a search for just this resource.
     */
    public function search(string $term, int $limit = 10, ?array $post = null): array;

    /**
     * Returns the name of the resource.
     */
    public function resourceName(): string;

    /**
     * Returns a link to the admin area URL main list
     * for this resource.
     */
    public function resourceUrl(): string;

    /**
     * Returns the name of the view to use when
     * displaying the list of results for this
     * resource type. This should be relative
     * to the theme used to display the results.
     */
    public function resultView(): string;
}
