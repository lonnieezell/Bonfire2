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

use Bonfire\Actions\Jobs\RunAction;
use Bonfire\Actions\Models\ActionRunModel;
use Bonfire\Bonfire;
use Bonfire\Users\Models\UserModel;
use Bonfire\Users\User;
use RuntimeException;
use Throwable;

/**
 * An action run's lifecycle: runs it in the request or queues it, moves a
 * queued run through its statuses, and looks up its progress.
 */
class ActionRuns
{
    /**
     * How long, in seconds, a queued run's file can be downloaded after the run ends.
     */
    public const FILE_LIFETIME = DAY;

    /**
     * Runs the action on the records now, or queues the run.
     *
     * @param list<int|string>      $ids
     * @param array<string, string> $input what the admin typed into the action's `fields()`, by handle
     */
    public function dispatch(string $list, Action $action, User $actor, array $ids, array $input = []): RunOutcome
    {
        $queueReady = isset(config('Queue')?->jobHandlers[RunAction::NAME]);

        if ($action->queue === Queue::Always && ! $queueReady) {
            return RunOutcome::ran(false, lang('Actions.queueMissing'));
        }

        $runs = model(ActionRunModel::class);
        $run  = [
            'list'    => $list,
            'action'  => $action->name(),
            'user_id' => $actor->id,
            'ids'     => json_encode($ids),
            'inputs'  => json_encode($input),
            'total'   => count($ids),
        ];

        if ($queueReady && ($action->queue === Queue::Always || ($action->queue === Queue::Auto && count($ids) > $action->queueAbove))) {
            $runId  = $runs->insert($run + ['status' => RunStatus::Queued]);
            $pushed = service('queue')->push(RunAction::QUEUE, RunAction::NAME, ['run' => $runId]);

            if (! $pushed->getStatus()) {
                log_message('error', (string) $pushed->getError());
                $runs->update($runId, ['status' => RunStatus::Failed, 'message' => lang('Bonfire.unknownError')]);

                return RunOutcome::ran(false, lang('Bonfire.unknownError'));
            }

            return RunOutcome::queued($runId);
        }

        $context = new ActionContext(service('actions')->for($list)->source(), $action, $actor, $ids, input: $input);

        // Runs in the request are recorded once they end, as the audit trail
        // of who did what. Recording them first would leave a run killed by a
        // timeout "running" for good.
        try {
            $result = $action->handle($context);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            $runs->insert($run + ['status' => RunStatus::Failed, 'processed' => $context->processed(), 'skipped' => $context->skipped(), 'message' => lang('Bonfire.unknownError')]);

            return RunOutcome::ran(false, lang('Bonfire.unknownError'));
        }

        $succeeded = $context->succeeded($result);
        $outcome   = RunOutcome::ran($succeeded, $context->summary($result), $this->keptFile($succeeded, $result));

        $runs->insert($run + [
            'status'    => $outcome->succeeded ? RunStatus::Done : RunStatus::Failed,
            'processed' => $context->processed(),
            'skipped'   => $context->skipped(),
            'message'   => $outcome->message,
        ]);

        return $outcome;
    }

    /**
     * Runs a queued run in a worker, recording its progress after each chunk.
     */
    public function process(int $runId): void
    {
        $runs = model(ActionRunModel::class);
        $run  = $runs->find($runId) ?? throw new RuntimeException("Action run {$runId} not found.");

        // A worker serves no admin page, so modules haven't registered their actions yet.
        if (service('actions')->find($run['list'], $run['action']) === null) {
            (new Bonfire())->boot(admin: true);
        }

        $action = service('actions')->find($run['list'], $run['action'])
            ?? throw new RuntimeException("Unknown action {$run['list']}/{$run['action']}.");
        $actor = model(UserModel::class)->find($run['user_id'])
            ?? throw new RuntimeException("The user who started action run {$runId} is gone.");

        $runs->update($runId, ['status' => RunStatus::Running]);

        $context = new ActionContext(
            service('actions')->for($run['list'])->source(),
            $action,
            $actor,
            json_decode($run['ids'], true),
            afterChunk: static fn (int $processed, int $skipped) => $runs->update($runId, ['processed' => $processed, 'skipped' => $skipped]),
            input: json_decode($run['inputs'] ?? '[]', true) ?? [],
        );

        try {
            $result = $action->handle($context);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            $runs->update($runId, ['status' => RunStatus::Failed, 'message' => lang('Bonfire.unknownError')]);

            // So the queue records the failed job too
            throw $e;
        }

        $succeeded = $context->succeeded($result);
        $file      = $this->keptFile($succeeded, $result);

        $runs->update($runId, [
            'status'    => $succeeded ? RunStatus::Done : RunStatus::Failed,
            'processed' => $context->processed(),
            'skipped'   => $context->skipped(),
            'message'   => $context->summary($result),
            'file'      => $file instanceof ResultFile ? json_encode($file) : null,
        ]);
    }

    /**
     * The result's file, if the run succeeded. A file from a run that did nothing has nothing in it to send, so it's deleted.
     */
    private function keptFile(bool $succeeded, ActionResult $result): ?ResultFile
    {
        if (! $succeeded && $result->file instanceof ResultFile) {
            @unlink($result->file->path);
        }

        return $succeeded ? $result->file : null;
    }

    /**
     * The file a queued run wrote, if the viewer started the run, it ended less than a day ago, and the file is still there.
     */
    public function file(int $runId, User $viewer): ?ResultFile
    {
        $run  = $this->progress($runId, $viewer);
        $file = json_decode($run['file'] ?? '', true);

        if (! is_array($file) || $run['updated_at']->getTimestamp() < time() - self::FILE_LIFETIME || ! is_file($file['path'])) {
            return null;
        }

        return new ResultFile($file['path'], $file['name']);
    }

    /**
     * A queued run, if it exists and the viewer started it.
     *
     * @return array<string, mixed>|null A row of action_runs
     */
    public function progress(int $runId, User $viewer): ?array
    {
        $run = model(ActionRunModel::class)->find($runId);

        return $run !== null && (int) $run['user_id'] === $viewer->id ? $run : null;
    }

    /**
     * The label of a run's action, or its name when the action is no longer registered.
     *
     * @param array<string, mixed> $run A row of action_runs
     */
    public function label(array $run): string
    {
        return service('actions')->find($run['list'], $run['action'])?->label ?: $run['action'];
    }

    /**
     * The toast of the run the current admin just queued, if any. It polls until the run ends.
     */
    public function toast(): string
    {
        $runId = session('actionRun');
        $run   = $runId && ($viewer = auth()->user()) instanceof User ? $this->progress((int) $runId, $viewer) : null;

        return $run === null ? '' : view('Bonfire\Actions\Views\_run', ['run' => $run]);
    }
}
