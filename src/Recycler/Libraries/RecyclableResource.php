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

use Bonfire\Recycler\Interfaces\CustomRecyclerPurge;
use Bonfire\Recycler\Interfaces\CustomRecyclerQuery;
use Bonfire\Recycler\Interfaces\CustomRecyclerRestore;
use CodeIgniter\Model;

/**
 * Something the Recycler can list, restore and purge: a soft-deleting model.
 * The defaults for all three live here; a model replaces one by implementing
 * the matching interface from `Bonfire\Recycler\Interfaces`.
 */
class RecyclableResource
{
    private ?Model $model = null;

    /**
     * @param string       $label      Display name, also the language file that may hold a localized one
     * @param class-string $modelClass
     * @param list<string> $columns    The columns shown to identify a record
     */
    public function __construct(
        private readonly string $alias,
        private readonly string $label,
        private readonly string $modelClass,
        private readonly array $columns,
    ) {
    }

    public function alias(): string
    {
        return $this->alias;
    }

    /**
     * The label from the `<label>.recycler.label` language line, if there is one.
     */
    public function label(): string
    {
        return $this->localized("{$this->label}.recycler.label", $this->label);
    }

    /**
     * @return list<string>
     */
    public function columns(): array
    {
        return $this->columns;
    }

    /**
     * The column names from the `<label>.recycler.columns.<column>` language lines, where there are some.
     *
     * @return array<string, string> The localized name by column
     */
    public function localizedColumns(): array
    {
        $names = [];

        foreach ($this->columns as $column) {
            $names[$column] = $this->localized("{$this->label}.recycler.columns.{$column}", $column);
        }

        return $names;
    }

    public function primaryKey(): string
    {
        return $this->model()->primaryKey;
    }

    /**
     * The deleted records, most recently deleted first.
     *
     * @return array{items: array, pager: mixed}
     */
    public function deleted(int $perPage): array
    {
        $model = $this->model();

        if ($model instanceof CustomRecyclerQuery) {
            $model = $model->setupRecycler();
        }

        $items = $model
            ->asArray()
            ->onlyDeleted()
            ->orderBy($model->deletedField, 'desc')
            ->paginate($perPage);

        return ['items' => $items, 'pager' => $model->pager];
    }

    public function restore(int $id): bool
    {
        $model = $this->model();

        if ($model instanceof CustomRecyclerRestore) {
            return $model->recyclerRestore($id);
        }

        if (! $this->isDeleted($id)) {
            return false;
        }

        return (bool) $model->where($model->primaryKey, $id)
            ->set([$model->deletedField => null])
            ->update();
    }

    public function purge(int $id): bool
    {
        $model = $this->model();

        if ($model instanceof CustomRecyclerPurge) {
            return $model->recyclerPurge($id);
        }

        return $this->isDeleted($id) && (bool) $model->delete($id, true);
    }

    public function errors(): array
    {
        return $this->model()->errors();
    }

    private function model(): Model
    {
        return $this->model ??= model($this->modelClass);
    }

    private function isDeleted(int $id): bool
    {
        $model = $this->model();

        return $model->onlyDeleted()->where($model->primaryKey, $id)->countAllResults() > 0;
    }

    private function localized(string $key, string $default): string
    {
        $value = lang($key);

        return $value === $key ? $default : $value;
    }
}
