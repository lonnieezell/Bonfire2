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

    /**
     * @var array<int|string, object> The records the page shows, by ID
     */
    private array $shown = [];

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
     * @param object|null $record When given, leaves out actions whose `allows()` rejects it
     *
     * @return list<Action> The actions $user may run from a row
     */
    public function row(User $user, ?object $record = null): array
    {
        return $this->permitted(
            $user,
            static fn (Action $action) => $action->placement->inRow() && ($record === null || $action->allows($user, $record)),
        );
    }

    /**
     * Hands over the records a page shows, in place of any handed over before, so their row
     * actions don't load each one.
     *
     * @param iterable<object> $records Each with an `id`
     */
    public function showing(iterable $records): static
    {
        $this->shown = [];

        foreach ($records as $record) {
            $this->shown[$record->id] = $record;
        }

        return $this;
    }

    /**
     * @return list<Action> The actions $user may run from the row of record $id
     */
    public function rowFor(User $user, int|string $id): array
    {
        if ($this->row($user) === []) {
            return [];
        }

        $record = $this->shown[$id] ?? $this->source()->find([$id])[0] ?? null;

        return $record === null ? [] : $this->row($user, $record);
    }

    /**
     * Whether $user may check rows to run bulk actions on them, or, given $record, check its row.
     */
    public function selectable(User $user, ?object $record = null): bool
    {
        foreach ($this->bulk($user) as $action) {
            if ($record === null || $action->allows($user, $record)) {
                return true;
            }
        }

        return false;
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
