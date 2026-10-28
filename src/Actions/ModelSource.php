<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Actions;

use CodeIgniter\Model;

/**
 * Loads a list's records through a model.
 */
class ModelSource implements RecordSource
{
    /**
     * @param class-string<Model> $model
     */
    public function __construct(private readonly string $model)
    {
    }

    public function find(array $ids): array
    {
        return $ids === [] ? [] : model($this->model, false)->find($ids);
    }
}
