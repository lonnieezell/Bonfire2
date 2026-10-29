<?php

namespace Tests\Actions;

use Bonfire\Actions\Jobs\RunAction;
use Bonfire\Actions\Models\ActionRunModel;
use Bonfire\Actions\ModelSource;
use Bonfire\Actions\Queue;
use Bonfire\Actions\RunStatus;
use Bonfire\Users\Models\UserModel;
use Bonfire\Users\User;
use CodeIgniter\Config\Services;
use CodeIgniter\Queue\Interfaces\QueueInterface;
use CodeIgniter\Queue\QueuePushResult;
use RuntimeException;
use Tests\Support\Actions\TouchUsers;
use Tests\Support\TestCase;

/**
 * An action run's lifecycle, without HTTP: running or queueing it, the job's
 * status transitions and progress lookup.
 *
 * @internal
 */
final class ActionRunsTest extends TestCase
{
    protected $refresh = true;
    private User $admin;
    private TouchUsers $touch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createUser();

        $this->touch = new TouchUsers();
        service('actions')->for('test-users')
            ->records(new ModelSource(UserModel::class))
            ->add($this->touch);
    }

    protected function tearDown(): void
    {
        Services::resetSingle('queue');

        parent::tearDown();
    }

    /**
     * @return list<int>
     */
    private function users(int $count): array
    {
        return array_map(fn () => $this->createUser()->id, range(1, $count));
    }

    public function testDispatchRunsInTheRequestAtOrBelowTheThreshold(): void
    {
        $this->touch->queueAbove = 2;
        $ids                     = $this->users(2);

        $outcome = service('actionRuns')->dispatch('test-users', $this->touch, $this->admin, $ids);

        $this->assertNull($outcome->runId);
        $this->assertTrue($outcome->succeeded);
        $this->assertSame('2 users touched.', $outcome->message);
        $this->assertSame($ids, $this->touch->touched);
    }

    public function testDispatchQueuesRunsAboveTheThreshold(): void
    {
        $this->touch->queueAbove = 1;

        $outcome = service('actionRuns')->dispatch('test-users', $this->touch, $this->admin, $this->users(2));

        $this->assertNotNull($outcome->runId);
        $this->assertSame([], $this->touch->touched);
        $this->assertSame(RunStatus::Queued, service('actionRuns')->progress($outcome->runId, $this->admin)['status']);
        $this->seeInDatabase('queue_jobs', ['queue' => RunAction::QUEUE]);
    }

    public function testDispatchRefusesAlwaysWithoutAQueue(): void
    {
        config('Queue')->jobHandlers = [];
        $this->touch->queue          = Queue::Always;

        $outcome = service('actionRuns')->dispatch('test-users', $this->touch, $this->admin, $this->users(1));

        $this->assertNull($outcome->runId);
        $this->assertFalse($outcome->succeeded);
        $this->assertSame(lang('Actions.queueMissing'), $outcome->message);
        $this->assertSame([], $this->touch->touched);
    }

    public function testAFailedPushMarksTheRunFailed(): void
    {
        $this->touch->queue = Queue::Always;
        $queue              = $this->createMock(QueueInterface::class);
        $queue->method('push')->willReturn(QueuePushResult::failure('Queue is down'));
        Services::injectMock('queue', $queue);

        $outcome = service('actionRuns')->dispatch('test-users', $this->touch, $this->admin, $this->users(1));

        $this->assertNull($outcome->runId);
        $this->assertFalse($outcome->succeeded);
        $this->assertSame(lang('Bonfire.unknownError'), $outcome->message);
        $this->seeInDatabase('action_runs', ['list' => 'test-users', 'status' => 'failed']);
    }

    /**
     * Queues the touch action on the records and returns the run's ID.
     *
     * @param list<int> $ids
     */
    private function queued(array $ids): int
    {
        $this->touch->queue = Queue::Always;

        return service('actionRuns')->dispatch('test-users', $this->touch, $this->admin, $ids)->runId;
    }

    public function testProcessRunsTheActionAndRecordsItsProgress(): void
    {
        $ids   = $this->users(2);
        $runId = $this->queued([$this->admin->id, ...$ids]);

        service('actionRuns')->process($runId);

        $run = service('actionRuns')->progress($runId, $this->admin);
        $this->assertSame($ids, $this->touch->touched);
        $this->assertSame(RunStatus::Done, $run['status']);
        $this->assertSame([2, 1, '2 users touched. Skipped 1.'], [(int) $run['processed'], (int) $run['skipped'], $run['message']]);
    }

    public function testAFailingRunIsMarkedFailedAndRethrown(): void
    {
        $this->touch->fails = true;
        $runId              = $this->queued($this->users(1));

        try {
            service('actionRuns')->process($runId);
            $this->fail('The run should rethrow so the queue keeps the failed job.');
        } catch (RuntimeException $e) {
            $this->assertSame('Touch failed', $e->getMessage());
        }

        $run = service('actionRuns')->progress($runId, $this->admin);
        $this->assertSame(RunStatus::Failed, $run['status']);
        $this->assertSame(lang('Bonfire.unknownError'), $run['message']);
    }

    public function testProcessLoadsActionsInAWorkerThatHasNotBootedTheAdmin(): void
    {
        $target = $this->createUser();
        $runId  = model(ActionRunModel::class)->insert([
            'list'    => 'users',
            'action'  => 'delete-users',
            'user_id' => $this->admin->id,
            'ids'     => json_encode([$target->id]),
            'total'   => 1,
            'status'  => RunStatus::Queued,
        ]);
        Services::resetSingle('actions');

        service('actionRuns')->process($runId);

        $this->assertNull(model(UserModel::class)->find($target->id));
    }

    public function testOnlyTheAdminWhoStartedARunSeesItsProgress(): void
    {
        $runId = $this->queued($this->users(1));

        $this->assertNotNull(service('actionRuns')->progress($runId, $this->admin));
        $this->assertNull(service('actionRuns')->progress($runId, $this->createUser()));
        $this->assertNull(service('actionRuns')->progress($runId + 1, $this->admin));
    }

    public function testToastShowsTheRunJustQueuedToTheAdminWhoQueuedIt(): void
    {
        $this->actingAs($this->admin);
        $this->assertSame('', service('actionRuns')->toast());

        $runId                 = $this->queued($this->users(1));
        $_SESSION['actionRun'] = $runId;
        $this->assertStringContainsString(url_to('action-run', $runId), (string) service('actionRuns')->toast());

        auth()->logout();
        $this->actingAs($this->createUser());
        $_SESSION['actionRun'] = $runId;
        $this->assertSame('', service('actionRuns')->toast());
    }
}
