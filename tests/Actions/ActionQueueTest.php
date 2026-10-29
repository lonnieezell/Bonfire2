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
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Queue\Interfaces\QueueInterface;
use CodeIgniter\Queue\QueuePushResult;
use CodeIgniter\Test\TestResponse;
use Tests\Support\Actions\TouchUsers;
use Tests\Support\TestCase;

/**
 * Queued runs: when an action goes to the queue, the job that runs it, and its progress.
 *
 * @internal
 */
final class ActionQueueTest extends TestCase
{
    protected $refresh = true;
    private User $admin;
    private TouchUsers $touch;

    protected function setUp(): void
    {
        parent::setUp();

        setting('Site.siteOnline', true);

        $this->admin = $this->createUser();
        $this->admin->addGroup('admin');

        $this->touch = new TouchUsers();
        service('actions')->for('test-users')
            ->records(new ModelSource(UserModel::class))
            ->add($this->touch);
    }

    /**
     * @param list<int> $ids
     */
    private function runAction(array $ids): TestResponse
    {
        return $this->actingAs($this->admin)
            ->post(ADMIN_AREA . '/actions/test-users/touch-users', [csrf_token() => csrf_hash(), 'selects' => $ids]);
    }

    /**
     * @return list<int>
     */
    private function users(int $count): array
    {
        return array_map(fn () => $this->createUser()->id, range(1, $count));
    }

    /**
     * Runs the action and returns the ID of the run it queued.
     *
     * @param list<int> $ids
     */
    private function queueAction(array $ids): int
    {
        $this->runAction($ids)->assertSessionHas('actionRun');

        return $_SESSION['actionRun'];
    }

    private function withoutQueue(): void
    {
        config('Queue')->jobHandlers = [];
    }

    public function testAutoQueuesRunsAboveTheThreshold(): void
    {
        $this->touch->queueAbove = 1;
        $ids                     = $this->users(2);

        $runId = $this->queueAction($ids);

        $this->assertSame([], $this->touch->touched);
        $this->seeInDatabase('action_runs', ['id' => $runId, 'list' => 'test-users', 'action' => 'touch-users', 'user_id' => $this->admin->id, 'total' => 2, 'status' => 'queued']);
        $this->seeInDatabase('queue_jobs', ['queue' => RunAction::QUEUE]);
    }

    public function testTheJobRunsTheActionAndRecordsItsProgress(): void
    {
        $this->touch->queue = Queue::Always;
        $ids                = $this->users(2);
        $runId              = $this->queueAction([$this->admin->id, ...$ids]);

        (new RunAction(['run' => $runId]))->process();

        $this->assertSame($ids, $this->touch->touched);
        $this->seeInDatabase('action_runs', ['id' => $runId, 'status' => 'done', 'processed' => 2, 'skipped' => 1, 'message' => '2 users touched. Skipped 1.']);
    }

    public function testAFailedPushMarksTheRunFailed(): void
    {
        $this->touch->queue = Queue::Always;
        $queue              = $this->createMock(QueueInterface::class);
        $queue->method('push')->willReturn(QueuePushResult::failure('Queue is down'));
        Services::injectMock('queue', $queue);

        $this->runAction($this->users(1))->assertSessionHas('error', lang('Bonfire.unknownError'));

        $this->seeInDatabase('action_runs', ['list' => 'test-users', 'status' => 'failed']);
    }

    public function testAlwaysRefusesWithoutAQueue(): void
    {
        $this->withoutQueue();
        $this->touch->queue = Queue::Always;

        $response = $this->runAction($this->users(1));

        $response->assertSessionHas('error', lang('Actions.queueMissing'));
        $this->assertSame([], $this->touch->touched);
        $this->dontSeeInDatabase('action_runs', ['list' => 'test-users']);
    }

    public function testAutoRunsInTheRequestWithoutAQueue(): void
    {
        $this->withoutQueue();
        $this->touch->queueAbove = 1;
        $ids                     = $this->users(2);

        $this->runAction($ids)->assertSessionHas('message', '2 users touched.');
        $this->assertSame($ids, $this->touch->touched);
    }

    public function testNeverRunsInTheRequestAboveTheThreshold(): void
    {
        $this->touch->queue      = Queue::Never;
        $this->touch->queueAbove = 1;
        $ids                     = $this->users(2);

        $this->runAction($ids)->assertSessionHas('message', '2 users touched.');
        $this->assertSame($ids, $this->touch->touched);
    }

    public function testProgressPollsUntilTheRunEnds(): void
    {
        $runs  = model(ActionRunModel::class);
        $runId = $runs->insert(['list' => 'test-users', 'action' => 'touch-users', 'user_id' => $this->admin->id, 'ids' => '[]', 'total' => 4, 'processed' => 1, 'skipped' => 1, 'status' => RunStatus::Running]);

        $running = (string) $this->actingAs($this->admin)->withHeaders(['HX-Request' => 'true'])->get(route_to('action-run', $runId))->response()->getBody();
        $this->assertStringNotContainsString('<html', $running);
        $this->assertStringContainsString('hx-trigger="every 2s"', $running);
        $this->assertStringContainsString(lang('Actions.running', [2, 4]), $running);

        $runs->update($runId, ['status' => RunStatus::Done, 'message' => 'All touched.']);

        $done = (string) $this->actingAs($this->admin)->withHeaders(['HX-Request' => 'true'])->get(route_to('action-run', $runId))->response()->getBody();
        $this->assertStringNotContainsString('hx-trigger', $done);
        $this->assertStringContainsString('All touched.', $done);
    }

    public function testOnlyTheAdminWhoStartedARunSeesIt(): void
    {
        $runId = model(ActionRunModel::class)->insert(['list' => 'test-users', 'action' => 'touch-users', 'user_id' => $this->admin->id, 'ids' => '[]', 'total' => 1, 'status' => RunStatus::Queued]);
        $other = $this->createUser();
        $other->addGroup('admin');

        $this->expectException(PageNotFoundException::class);

        $this->actingAs($other)->get(route_to('action-run', $runId));
    }
}
