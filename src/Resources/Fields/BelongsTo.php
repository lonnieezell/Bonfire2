<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Resources\Fields;

use CodeIgniter\Model;

/**
 * A `belongsTo` field: one record of another model, chosen from a select of their titles.
 */
class BelongsTo extends Field
{
    protected const TYPE = 'belongsTo';

    /**
     * @param class-string<Model> $model
     */
    public function model(string $model): static
    {
        return $this->with(['model' => $model]);
    }

    /**
     * The column of the model's table that names a record.
     */
    public function title(string $title): static
    {
        return $this->with(['title' => $title]);
    }
}
