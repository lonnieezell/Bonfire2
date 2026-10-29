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
use Closure;
use Generator;

/**
 * One run of an action: who runs it and on which records. The same context
 * serves a run in the request and one in a queue worker.
 */
class ActionContext
{
    private int $processed = 0;
    private int $skipped   = 0;

    /**
     * @param list<int|string>                                   $ids
     * @param (Closure(int $processed, int $skipped): void)|null $afterChunk Called once each chunk is handled
     */
    public function __construct(
        private readonly RecordSource $source,
        private readonly Action $action,
        private readonly User $actor,
        private readonly array $ids,
        private readonly int $chunkSize = 100,
        private readonly ?Closure $afterChunk = null,
    ) {
    }

    public function actor(): User
    {
        return $this->actor;
    }

    /**
     * The records the action may touch, a chunk at a time. Missing records and
     * ones the action's `allows()` rejects are skipped.
     *
     * @return Generator<int, list<object>>
     */
    public function chunks(): Generator
    {
        foreach (array_chunk($this->ids, $this->chunkSize) as $ids) {
            $records = array_values(array_filter(
                $this->source->find($ids),
                fn (object $record) => $this->action->allows($this->actor, $record),
            ));

            $this->processed += count($records);
            $this->skipped += count($ids) - count($records);

            if ($records !== []) {
                yield $records;
            }

            if ($this->afterChunk instanceof Closure) {
                ($this->afterChunk)($this->processed, $this->skipped);
            }
        }
    }

    /**
     * The records the action may touch, one at a time.
     *
     * @return Generator<int, object>
     */
    public function entities(): Generator
    {
        foreach ($this->chunks() as $records) {
            foreach ($records as $record) {
                yield $record;
            }
        }
    }

    /**
     * Records handed to the action so far.
     */
    public function processed(): int
    {
        return $this->processed;
    }

    /**
     * Records skipped so far, because they were missing or not allowed.
     */
    public function skipped(): int
    {
        return $this->skipped;
    }

    /**
     * Whether the run did something: it didn't fail, and not every record was skipped.
     */
    public function succeeded(ActionResult $result): bool
    {
        return ! $result->failed && $this->processed > 0;
    }

    /**
     * The result's message for this run, noting any skipped records.
     */
    public function summary(ActionResult $result): string
    {
        if ($this->processed === 0 && ! $result->failed) {
            return lang('Actions.noneAllowed');
        }

        $message = $result->text($this->processed);

        return $this->skipped > 0 ? $message . ' ' . lang('Actions.skipped', [$this->skipped]) : $message;
    }
}
