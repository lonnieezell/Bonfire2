<?php

namespace Bonfire\Modules\Search\Interfaces;

interface SearchProviderInterface
{
    /**
     * Performs a search for just this resource.
     *
     * @param string     $term
     * @param int        $limit
     * @param array|null $post
     *
     * @return array
     */
    public function search(string $term, int $limit=10, array $post=null): array;

    /**
     * Returns the name of the resource.
     *
     * @return string
     */
    public function resourceName(): string;

    /**
     * Returns a link to the admin area URL main list
     * for this resource.
     *
     * @return string
     */
    public function resourceUrl(): string;

    /**
     * Returns the name of the view to use when
     * displaying the list of results for this
     * resource type. This should be relative
     * to the theme used to display the results.
     *
     * @return string
     */
    public function resultView(): string;
}
