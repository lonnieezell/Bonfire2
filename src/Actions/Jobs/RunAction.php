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

use Bonfire\Actions\ActionContext;
use Bonfire\Actions\Models\ActionRunModel;
use Bonfire\Bonfire;
use Bonfire\Users\Models\UserModel;
use CodeIgniter\Queue\BaseJob;
use RuntimeException;
use Throwable;

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
        $runs = model(ActionRunModel::class);
        $run  = $runs->find($this->data['run']) ?? throw new RuntimeException("Action run {$this->data['run']} not found.");

        // A worker serves no admin page, so modules haven't registered their actions yet.
        if (service('actions')->find($run['list'], $run['action']) === null) {
            (new Bonfire())->boot(admin: true);
        }

        $action = service('actions')->find($run['list'], $run['action'])
            ?? throw new RuntimeException("Unknown action {$run['list']}/{$run['action']}.");
        $actor = model(UserModel::class)->find($run['user_id'])
            ?? throw new RuntimeException("The user who started action run {$run['id']} is gone.");

        $runs->update($run['id'], ['status' => 'running']);

        $context = new ActionContext(
            service('actions')->for($run['list'])->source(),
            $action,
            $actor,
            json_decode($run['ids'], true),
            afterChunk: static fn (int $processed, int $skipped) => $runs->update($run['id'], ['processed' => $processed, 'skipped' => $skipped]),
        );

        try {
            $result = $action->handle($context);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            $runs->update($run['id'], ['status' => 'failed', 'message' => lang('Bonfire.unknownError')]);

            // So the queue records the failed job too
            throw $e;
        }

        $runs->update($run['id'], [
            'status'    => $context->succeeded($result) ? 'done' : 'failed',
            'processed' => $context->processed(),
            'skipped'   => $context->skipped(),
            'message'   => $context->summary($result),
        ]);
    }
}
