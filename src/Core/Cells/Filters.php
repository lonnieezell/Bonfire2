<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Core\Cells;

use RuntimeException;

class Filters
{
    /**
     * A view cell that displays the list of available filters.
     *
     * @param mixed $params
     *
     * @throws RuntimeException
     */
    public function renderList($params = [])
    {
        if (! isset($params['model'])) {
            throw new RuntimeException('You must provide the Filter view cell with the model to use.');
        }

        $model = model($params['model']);
        if (! method_exists($model, 'getFilters')) {
            throw new RuntimeException('Your model is not compatible with Filters. See Bonfire\Traits\Filterable.');
        }

        return view(config('Bonfire')->views['filter_list'], [
            'filters' => $model->getFilters(),
            'target'  => $params['target'] ?? null,
        ]);
    }
}
