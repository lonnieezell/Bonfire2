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
 * Where a queued action run is: waiting for a worker, running, or ended.
 */
enum RunStatus: string
{
    case Queued  = 'queued';
    case Running = 'running';
    case Done    = 'done';
    case Failed  = 'failed';

    public function ended(): bool
    {
        return $this === self::Done || $this === self::Failed;
    }

    /**
     * Its `<x-status>` tone.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Queued  => 'muted',
            self::Running => 'info',
            self::Done    => 'ok',
            self::Failed  => 'danger',
        };
    }

    public function label(): string
    {
        return lang('Actions.status.' . $this->value);
    }
}
