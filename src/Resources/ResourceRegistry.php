<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Resources;

use Bonfire\Fields\Field;
use Bonfire\Menus\MenuItem;
use InvalidArgumentException;

/**
 * The resources that modules have registered, from `service('resources')`.
 */
class ResourceRegistry
{
    /**
     * The registered resources, by slug.
     */
    private array $resources = [];

    /**
     * Registers a resource and adds its list to the sidebar, for those who have `{permission}.list`.
     * It also puts the resource's soft-deleted records in the Recycler, shown by the `$search` columns, and
     * its `$search` columns in the admin's global search. Call it from a module's initAdmin(), after the
     * module's routes exist.
     *
     * @param class-string $class      a class that extends Resource
     * @param string       $collection the sidebar collection that gets the item
     * @param string       $icon       the Font Awesome classes of the item's icon
     */
    public function register(string $class, string $collection = 'content', string $icon = 'fas fa-table'): Resource
    {
        if (! is_a($class, Resource::class, true)) {
            throw new InvalidArgumentException("{$class} is not a " . Resource::class . '.');
        }

        $resource                         = new $class();
        $this->resources[$resource->slug] = $resource;

        if ($resource->allows('list')) {
            service('menus')->menu('sidebar')->collection($collection)->addItem(new MenuItem([
                'title'           => $resource->title(),
                'namedRoute'      => $resource->slug . '-list',
                'fontAwesomeIcon' => $icon,
                'permission'      => $resource->permission . '.list',
            ]));
        }

        // The Recycler lists deleted records, so a model that deletes for good has nothing to show it.
        if (model($resource->model, false)->useSoftDeletes) {
            service('recycler')->register($resource->slug, $resource->title(), $resource->model, $this->recyclerColumns($resource));
        }

        return $resource;
    }

    public function get(string $slug): ?Resource
    {
        return $this->resources[$slug] ?? null;
    }

    /**
     * The global search providers of the registered resources that the signed-in user may list.
     *
     * @return list<ResourceSearchProvider>
     */
    public function searchProviders(): array
    {
        $providers = [];

        foreach ($this->resources as $resource) {
            $provider = new ResourceSearchProvider($resource);

            if ($resource->search !== [] && $provider->isAllowed()) {
                $providers[] = $provider;
            }
        }

        return $providers;
    }

    /**
     * What identifies a deleted record: the `$search` columns, or else the list's columns.
     *
     * @return list<string>
     */
    private function recyclerColumns(Resource $resource): array
    {
        return $resource->search !== []
            ? $resource->search
            : array_map(static fn (Field $column): string => $column->handle, $resource->columns());
    }
}
