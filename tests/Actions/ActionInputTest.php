<?php

namespace Tests\Actions;

use Bonfire\Actions\Confirm;
use Bonfire\Actions\Jobs\RunAction;
use Bonfire\Actions\Models\ActionRunModel;
use Bonfire\Actions\ModelSource;
use Bonfire\Actions\Queue;
use Bonfire\Users\Models\UserModel;
use Bonfire\Users\User;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\TestResponse;
use Tests\Support\Actions\NoteUsers;
use Tests\Support\TestCase;

/**
 * An action's `fields()`: asked for in the confirm dialog and on the confirm page, validated before anything runs,
 * and handed to `handle()` through `ActionContext::input()`.
 *
 * @internal
 */
final class ActionInputTest extends TestCase
{
    protected $refresh = true;
    private User $admin;
    private NoteUsers $note;

    protected function setUp(): void
    {
        parent::setUp();

        setting('Site.siteOnline', true);

        $this->admin = $this->createUser();
        $this->admin->addGroup('admin');

        $this->note = new NoteUsers();
        service('actions')->for('test-users')
            ->records(new ModelSource(UserModel::class))
            ->add($this->note);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function runAction(array $data, bool $ajax = false, string $list = 'test-users'): TestResponse
    {
        $request = $this->actingAs($this->admin);

        if ($ajax) {
            $request = $request->withHeaders(['X-Requested-With' => 'XMLHttpRequest']);
        }

        return $request->post(ADMIN_AREA . '/actions/' . $list . '/note-users', [csrf_token() => csrf_hash(), ...$data]);
    }

    /**
     * @return list<int>
     */
    private function users(int $count): array
    {
        return array_map(fn () => $this->createUser()->id, range(1, $count));
    }

    public function testTheConfirmPageAsksForTheFieldsAndHandleGetsTheValue(): void
    {
        $ids = $this->users(2);

        $page = $this->runAction(['selects' => $ids]);

        $page->assertOK();
        $this->assertStringContainsString('<textarea name="fields[note]"', $page->getBody());
        $this->assertStringNotContainsString('data-error="note"', $page->getBody(), 'no error before anything was submitted');
        $this->assertSame([], $this->note->notes);

        $this->runAction(['selects' => $ids, 'confirmed' => '1', 'fields' => ['note' => "Line one\r\nLine two"]])->assertRedirect();

        $this->assertSame(["Line one\nLine two"], $this->note->notes);
        $this->assertSame($ids, $this->note->touched);
    }

    public function testInvalidInputShowsTheConfirmPageAgainWithItsErrorAndRunsNothing(): void
    {
        $ids = $this->users(2);

        $page = $this->runAction(['selects' => $ids, 'confirmed' => '1', 'fields' => ['note' => str_repeat('x', 21)]]);

        $page->assertOK();
        $this->assertStringContainsString('data-error="note"', $page->getBody());
        $this->assertStringContainsString(str_repeat('x', 21), $page->getBody(), 'what was typed stays in the input');
        $this->assertStringContainsString('<input type="hidden" name="selects[]" value="' . $ids[1] . '">', $page->getBody());
        $this->assertSame([], $this->note->notes);
        $this->assertSame([], $this->note->touched);
        $this->dontSeeInDatabase('action_runs', ['list' => 'test-users']);
    }

    public function testAMissingRequiredValueIsInvalidToo(): void
    {
        $page = $this->runAction(['selects' => $this->users(1), 'confirmed' => '1']);

        $this->assertStringContainsString('data-error="note"', $page->getBody());
        $this->assertSame([], $this->note->notes);
    }

    public function testTheFieldsComeBeforeTheActionsOwnQuestion(): void
    {
        $this->note->asks = Confirm::make('Note {count} users?');
        $ids              = $this->users(2);

        $page = $this->runAction(['selects' => $ids]);

        $page->assertSee('Note 2 users?');
        $this->assertStringContainsString('name="fields[note]"', $page->getBody());
    }

    public function testTheDialogGetsTheFieldsFromTheServerAndKeepsItselfOpenOnErrors(): void
    {
        $ids = $this->users(2);

        $invalid = $this->runAction(['selects' => $ids, 'confirmed' => '1', 'fields' => ['note' => '']], ajax: true);

        $invalid->assertStatus(422);
        $this->assertStringContainsString('data-error="note"', $invalid->getBody());
        $this->assertStringNotContainsString('<form', $invalid->getBody(), 'the fields for the dialog, not the confirm page');
        $this->assertSame([], $this->note->notes);

        $valid = $this->runAction(['selects' => $ids, 'confirmed' => '1', 'fields' => ['note' => 'Fine']], ajax: true);

        $valid->assertStatus(204);
        $valid->assertHeader('HX-Redirect');
        $valid->assertSessionHas('message', '2 users touched.');
        $this->assertSame(['Fine'], $this->note->notes);
    }

    public function testTheDialogFetchesTheFieldsWithoutAnyRecords(): void
    {
        $response = $this->actingAs($this->admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(ADMIN_AREA . '/actions/test-users/note-users/fields');

        $response->assertOK();
        $this->assertStringContainsString('<textarea name="fields[note]"', $response->getBody());
        $this->assertStringNotContainsString('data-error="note"', $response->getBody());
        $this->assertStringContainsString('name="' . csrf_token() . '" value="' . csrf_hash() . '" data-csrf', $response->getBody(), 'the current token, for the dialog to pass on');
    }

    public function testTheFieldsOfAnActionTheAdminMayNotRunAreNotShown(): void
    {
        $viewer = $this->createUser();
        $viewer->addPermission('admin.access');

        $this->expectException(PageNotFoundException::class);

        $this->actingAs($viewer)->get(ADMIN_AREA . '/actions/test-users/note-users/fields');
    }

    public function testButtonsOfAnActionWithFieldsAskThroughTheDialog(): void
    {
        $attributes = $this->note->confirmAttributes();

        $this->assertStringContainsString(' data-confirm="', $attributes, 'the dialog opens even when the action asks nothing itself');
        $this->assertStringContainsString(' data-confirm-fields="1"', $attributes);
    }

    public function testAQueuedRunGetsTheSameInput(): void
    {
        $this->note->queue = Queue::Always;
        $ids               = $this->users(2);

        $this->runAction(['selects' => $ids, 'confirmed' => '1', 'fields' => ['note' => 'Later']])->assertSessionHas('actionRun');
        $runId = $_SESSION['actionRun'];

        $this->assertSame([], $this->note->notes, 'nothing ran in the request');
        $this->assertSame(['note' => 'Later'], json_decode(model(ActionRunModel::class)->find($runId)['inputs'], true));

        (new RunAction(['run' => $runId]))->process();

        $this->assertSame(['Later'], $this->note->notes);
        $this->assertSame($ids, $this->note->touched);
    }

    public function testInvalidInputIsNotQueued(): void
    {
        $this->note->queue = Queue::Always;

        $this->runAction(['selects' => $this->users(1), 'confirmed' => '1', 'fields' => ['note' => '']]);

        $this->dontSeeInDatabase('action_runs', ['list' => 'test-users']);
    }

    public function testARunInTheRequestRecordsItsInput(): void
    {
        $this->runAction(['selects' => $this->users(1), 'confirmed' => '1', 'fields' => ['note' => 'Now']])->assertRedirect();

        $run = model(ActionRunModel::class)->where('list', 'test-users')->first();
        $this->assertSame(['note' => 'Now'], json_decode($run['inputs'], true));
    }

    public function testAnActionOnTheUsersListCanTakeInputToo(): void
    {
        service('actions')->for('users')
            ->records(new ModelSource(UserModel::class))
            ->add($this->note);
        $ids = $this->users(2);

        $page = $this->runAction(['selects' => $ids], list: 'users');
        $this->assertStringContainsString('<textarea name="fields[note]"', $page->getBody());

        $this->runAction(['selects' => $ids, 'confirmed' => '1', 'fields' => ['note' => str_repeat('x', 21)]], list: 'users');
        $this->assertSame([], $this->note->notes);

        $this->runAction(['selects' => $ids, 'confirmed' => '1', 'fields' => ['note' => 'Hello']], list: 'users')->assertRedirect();
        $this->assertSame(['Hello'], $this->note->notes);
        $this->assertSame($ids, $this->note->touched);
    }
}
