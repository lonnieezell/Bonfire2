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
     * Call it from a module's initAdmin(), after the module's routes exist.
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

        $allowed = model($resource->model, false)->allowedFields;
        $missing = array_values(array_filter(
            array_map(static fn (Field $field): string => $field->handle, $resource->formFields()),
            static fn (string $handle): bool => ! in_array($handle, $allowed, true),
        ));

        if ($missing === []) {
            return;
        }

        $message = sprintf(
            '%s has %s %s that %s not in the $allowedFields of %s, so saving would drop %s.',
            $resource::class,
            count($missing) === 1 ? 'the field' : 'the fields',
            '`' . implode('`, `', $missing) . '`',
            count($missing) === 1 ? 'is' : 'are',
            $resource->model,
            count($missing) === 1 ? 'its value' : 'their values',
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
}
