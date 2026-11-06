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

use Bonfire\Actions\ModelSource;
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

    private readonly bool $production;

    /**
     * @param bool|null $production whether a mistake in a resource is logged instead of thrown; the environment decides by default
     */
    public function __construct(?bool $production = null)
    {
        $this->production = $production ?? ENVIRONMENT === 'production';
    }

    /**
     * Registers a resource and adds its list to the sidebar, for those who have `{permission}.list`.
     * It also puts the resource's soft-deleted records in the Recycler, shown by the `$search` columns, and
     * its `$search` columns in the admin's global search, and its `actions()` on the action list named by its slug,
     * with the model as the record source. Call it from a module's initAdmin(), after the
     * module's routes exist.
     *
     * A resource that saves records needs every field in its model's `$allowedFields`, or the model would drop the
     * value. That throws a ResourceException, except in production, where it is logged and the resource still registers.
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

        $resource = new $class();

        $this->checkAllowedFields($resource);

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
        $model = model($resource->model, false);

        if ($model->useSoftDeletes) {
            // The `$search` columns identify a record, or else its key does: a list column may not be a column of the table.
            service('recycler')->register($resource->slug, $resource->title(), $resource->model, $resource->search ?: [$model->primaryKey]);
        }

        // The resource's actions go on the list named by its slug, so other modules can add to it.
        $actions = service('actions')->for($resource->slug)->records(new ModelSource($resource->model));

        foreach ($resource->actions() as $action) {
            $actions->add($action);
        }

        return $resource;
    }

    /**
     * @throws ResourceException outside production, when a field isn't in the model's `$allowedFields`
     */
    private function checkAllowedFields(Resource $resource): void
    {
        if (! $resource->allows('create') && ! $resource->allows('edit')) {
            return;
        }

        $model = model($resource->model, false);

        // A model that doesn't protect its fields saves them all.
        if (! $model->protectFields) {
            return;
        }

        $missing = array_diff(array_map(static fn (Field $field): string => $field->handle, $resource->formFields()), $model->allowedFields);

        if ($missing === []) {
            return;
        }

        $message = sprintf(
            '%s has fields that are not in the $allowedFields of %s, so saving would drop their values: `%s`.',
            $resource::class,
            $resource->model,
            implode('`, `', $missing),
        );

        if (! $this->production) {
            throw new ResourceException($message);
        }

        log_message('error', $message);
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
}
