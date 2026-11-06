<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Resources\Actions;

use Bonfire\Actions\DeleteAction;
use Bonfire\Resources\Column;
use Bonfire\Resources\Resource;
use RuntimeException;

/**
 * Deletes a resource's records through its model, which soft-deletes them into the Recycler when the model
 * uses soft deletes. It needs `{permission}.delete`. Add it to a resource's `actions()`:
 *
 *     public function actions(): array
 *     {
 *         return [new DeleteRecords($this)];
 *     }
 */
class DeleteRecords extends DeleteAction
{
    public function __construct(private readonly Resource $resource)
    {
        parent::__construct();

        $this->permission = $resource->permission . '.delete';
    }

    protected function delete(array $records): void
    {
        $model = model($this->resource->model, false);

        if (! $model->delete(array_map(static fn (object $record): mixed => Column::get($record, $model->primaryKey), $records))) {
            throw new RuntimeException(implode(' ', $model->errors()));
        }
    }

    protected function noun(): string
    {
        return mb_strtolower($this->resource->title());
    }
}
