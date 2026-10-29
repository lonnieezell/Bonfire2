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

/**
 * The action lists, by name. Any module may add actions to any list.
 */
class ActionRegistry
{
    /**
     * @var array<string, ActionList>
     */
    private array $lists = [];

    public function for(string $list): ActionList
    {
        return $this->lists[$list] ??= new ActionList($list);
    }

    /**
     * Finds an action without creating its list.
     */
    public function find(string $list, string $action): ?Action
    {
        return isset($this->lists[$list]) ? $this->lists[$list]->find($action) : null;
    }
}
