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

use Bonfire\Users\User;
use RuntimeException;

/**
 * The actions of one admin list, like 'users', and where its records come from.
 */
class ActionList
{
    /**
     * @var array<string, Action>
     */
    private array $actions = [];

    private ?RecordSource $source = null;

    public function __construct(public readonly string $name)
    {
    }

    public function records(RecordSource $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function source(): RecordSource
    {
        return $this->source ?? throw new RuntimeException("The '{$this->name}' action list has no record source.");
    }

    public function add(Action $action): static
    {
        $this->actions[$action->name()] = $action;

        return $this;
    }

    public function find(string $name): ?Action
    {
        return $this->actions[$name] ?? null;
    }

    /**
     * @return list<Action> The actions $user may run from a row
     */
    public function row(User $user): array
    {
        return $this->permitted($user, static fn (Action $action) => $action->placement->inRow());
    }

    /**
     * @return list<Action> The actions $user may run on the checked rows
     */
    public function bulk(User $user): array
    {
        return $this->permitted($user, static fn (Action $action) => $action->placement->inBulk());
    }

    /**
     * @param callable(Action): bool $placed
     *
     * @return list<Action>
     */
    private function permitted(User $user, callable $placed): array
    {
        return array_values(array_filter(
            $this->actions,
            static fn (Action $action) => $placed($action) && ($action->permission === '' || $user->can($action->permission)),
        ));
    }
}
