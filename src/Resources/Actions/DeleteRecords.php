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
use Bonfire\Actions\Queue;
use Bonfire\Resources\Column;
use Bonfire\Resources\Resource;
use Bonfire\Users\User;
use RuntimeException;

/**
 * Deletes a resource's records through its model, which soft-deletes them into the Recycler when the model
 * uses soft deletes. It needs `{permission}.delete`, and skips the records that the resource's
 * `can('delete', $record)` refuses. It always runs in the request, never in a queue. Add it to a resource's `actions()`:
 *
 *     public function actions(): array
 *     {
 *         return [new DeleteRecords($this)];
 *     }
 */
class DeleteRecords extends DeleteAction
{
    /**
     * The resource's `can('delete', $record)` asks the signed-in user, so the run stays in the request.
     */
    public Queue $queue = Queue::Never;

    public function __construct(private readonly Resource $resource)
    {
        parent::__construct();

        $this->permission = $resource->permission . '.delete';
    }

    /**
     * A record the resource won't let the user delete is skipped.
     */
    public function allows(User $actor, object $record): bool
    {
        return $this->resource->can('delete', $record);
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
