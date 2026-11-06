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
     * A view cell that displays the list of available filters, of the `model`,
     * or of the `resource` whose slug is given.
     *
     * @param mixed $params
     *
     * @throws RuntimeException
     */
    public function renderList($params = [])
    {
        if (isset($params['resource'])) {
            $source = service('resources')->get($params['resource'])
                ?? throw new RuntimeException('The Filter view cell found no resource with the slug ' . $params['resource'] . '.');
        } elseif (isset($params['model'])) {
            $source = model($params['model']);
        } else {
            throw new RuntimeException('You must provide the Filter view cell with the model to use.');
        }

        if (! method_exists($source, 'getFilters')) {
            throw new RuntimeException('Your model is not compatible with Filters. See Bonfire\Traits\Filterable.');
        }

        return view(config('Bonfire')->views['filter_list'], [
            'filters' => $source->getFilters(),
            'target'  => $params['target'] ?? null,
        ]);
    }
}
