<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Actions\Jobs;

use CodeIgniter\Queue\BaseJob;

/**
 * Runs a queued action from `spark queue:work`, recording its progress in
 * `action_runs` after each chunk. Needs codeigniter4/queue.
 */
class RunAction extends BaseJob
{
    /**
     * The queue runs are pushed to, and the job's name in `Config\Queue::$jobHandlers`.
     */
    public const QUEUE = 'bonfire-actions';

    public const NAME = 'bonfire-action';

    public function process()
    {
        service('actionRuns')->process($this->data['run']);
    }
}
