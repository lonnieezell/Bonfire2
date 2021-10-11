<?php

namespace Bonfire\Search;

/**
 * Handles working with the modules to
 * return results for the master search bar.
 */
class SearchEngine
{
    /**
     * Gets a set number of results from
     * each of the registered search providers.
     *
     * @param array|null $post
     */
    public function overview(array $post=null)
    {
        $providers = $this->locateProviders();
        $term = isset($post['search_term']) ? trim($post['search_term']) : null;

        if (empty($term)) {
            return;
        }

        $results = [];
        foreach($providers as $provider) {
            $name = $provider->resourceName();

            $results[$name] = [
                'provider' => $provider,
                'url' => $provider->resourceUrl(),
                'results' => $provider->search($term, 10, $post)
            ];
        }

        return $results;
    }

    /**
     * Finds the search provider classes.
     */
    private function locateProviders(): array
    {
        $locator = service('locator');
        $files = $locator->search('SearchProvider');

        if (empty($files)) {
            return [];
        }

        $providers = [];
        foreach($files as $file) {
            $class = $locator->findQualifiedNameFromPath($file);

            if (empty($class)) {
                continue;
            }

            $providers[] = new $class();
        }

        return $providers;
    }
}
