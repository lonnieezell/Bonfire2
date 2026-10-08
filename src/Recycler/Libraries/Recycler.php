<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Recycler\Libraries;

/**
 * The resources the Recycler can show. Modules register theirs from `initAdmin()`
 * through `service('recycler')`; an app can still add or override resources in
 * the `$resources` of `Config\Recycler`.
 */
class Recycler
{
    /**
     * @var array<string, array{label: string, model: string, columns: list<string>}>
     */
    private array $registered = [];

    /**
     * @param class-string $model
     * @param list<string> $columns The columns shown to identify a record
     */
    public function register(string $alias, string $label, string $model, array $columns): static
    {
        $this->registered[$alias] = ['label' => $label, 'model' => $model, 'columns' => $columns];

        return $this;
    }

    /**
     * @return array<string, RecyclableResource>
     */
    public function resources(): array
    {
        $definitions = array_merge($this->registered, (array) setting('Recycler.resources'));
        $resources   = [];

        foreach ($definitions as $alias => $definition) {
            // An app's entry only needs the keys it changes
            $definition = array_replace($this->registered[$alias] ?? [], $definition);

            $resources[$alias] = new RecyclableResource(
                (string) $alias,
                $definition['label'],
                $definition['model'],
                $definition['columns'],
            );
        }

        return $resources;
    }

    /**
     * @param string|null $alias The default resource when empty
     */
    public function find(?string $alias): ?RecyclableResource
    {
        $alias = $alias ?: setting('Recycler.defaultResource');

        return $this->resources()[$alias] ?? null;
    }
}
