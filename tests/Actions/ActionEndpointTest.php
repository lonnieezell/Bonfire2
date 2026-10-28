<?php

namespace Tests\Actions;

use Bonfire\Actions\Confirm;
use Bonfire\Actions\ModelSource;
use Bonfire\Users\Models\UserModel;
use Bonfire\Users\User;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\TestResponse;
use Tests\Support\Actions\TouchUsers;
use Tests\Support\TestCase;

/**
 * Running actions through POST admin/actions/{list}/{action}.
 *
 * @internal
 */
final class ActionEndpointTest extends TestCase
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

    private function runAction(array $data, string $action = 'touch-users', ?User $as = null): TestResponse
    {
        return $this->actingAs($as ?? $this->admin)
            ->post(ADMIN_AREA . '/actions/test-users/' . $action, [csrf_token() => csrf_hash(), ...$data]);
    }

    public function testRunsTheActionOnTheSelectedRecords(): void
    {
        $ids = [$this->createUser()->id, $this->createUser()->id];

        $response = $this->runAction(['selects' => $ids]);

        $response->assertRedirect();
        $response->assertSessionHas('message', '2 users touched.');
        $this->assertSame($ids, $this->touch->touched);
    }

    public function testRejectedAndMissingRecordsAreSkippedAndReported(): void
    {
        $other = $this->createUser()->id;

        $response = $this->runAction(['selects' => [$this->admin->id, $other, 999]]);

        $response->assertSessionHas('message', '1 users touched. Skipped 2.');
        $this->assertSame([$other], $this->touch->touched);
    }

    public function testARunThatTouchesNothingIsAnError(): void
    {
        $response = $this->runAction(['selects' => [$this->admin->id]]);

        $response->assertSessionHas('error', lang('Actions.noneAllowed'));
        $response->assertSessionMissing('message');
    }

    public function testRepeatedIdsCountOnce(): void
    {
        $other = $this->createUser()->id;

        $this->runAction(['selects' => [$other, $other]])->assertSessionHas('message', '1 users touched.');
        $this->assertSame([$other], $this->touch->touched);
    }

    public function testNeedsTheActionsPermission(): void
    {
        $viewer = $this->createUser();
        $viewer->addPermission('admin.access');

        $response = $this->runAction(['selects' => [$this->createUser()->id]], as: $viewer);

        $response->assertSessionHas('error', lang('Bonfire.notAuthorized'));
        $this->assertSame([], $this->touch->touched);
    }

    public function testNeedsACsrfToken(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(ADMIN_AREA . '/actions/test-users/touch-users', ['selects' => [$this->createUser()->id]]);

        $response->assertRedirect();
        $this->assertSame([], $this->touch->touched);
    }

    public function testOnlyRunsOverPost(): void
    {
        $this->expectException(PageNotFoundException::class);

        $this->actingAs($this->admin)->get(ADMIN_AREA . '/actions/test-users/touch-users?selects[]=' . $this->createUser()->id);
    }

    public function testUnknownActionIsNotFound(): void
    {
        $this->expectException(PageNotFoundException::class);

        $this->runAction(['selects' => [1]], 'nothing');
    }

    public function testNothingSelectedRunsNothing(): void
    {
        $response = $this->runAction([]);

        $response->assertSessionHas('error', lang('Bonfire.resourcesNotSelected', [lang('Actions.records')]));
        $this->assertSame([], $this->touch->touched);
    }

    public function testAConfirmedActionShowsItsConfirmPageUntilConfirmed(): void
    {
        $this->touch->asks = Confirm::make('Touch {count} users?')->body('They will feel it.')->button('Touch them');
        $ids               = [$this->createUser()->id, $this->createUser()->id];

        $page = $this->runAction(['selects' => $ids]);

        $page->assertOK();
        $page->assertSee('Touch 2 users?');
        $page->assertSee('They will feel it.');
        $this->assertStringContainsString('<input type="hidden" name="confirmed" value="1">', $page->getBody());
        $this->assertStringContainsString('<input type="hidden" name="selects[]" value="' . $ids[1] . '">', $page->getBody());
        $this->assertSame([], $this->touch->touched);

        $this->runAction(['selects' => $ids, 'confirmed' => '1'])->assertRedirect();
        $this->assertSame($ids, $this->touch->touched);
    }

    public function testTypeToConfirmNeedsTheRecordCount(): void
    {
        $this->touch->asks = Confirm::make('Touch {count} users?')->typeToConfirm();
        $ids               = [$this->createUser()->id, $this->createUser()->id];

        $page = $this->runAction(['selects' => $ids, 'confirmed' => '1', 'confirm_count' => '3']);

        $page->assertOK();
        $this->assertMatchesRegularExpression('#name="confirm_count"\s+class="form-control is-invalid"#', $page->getBody());
        $this->assertSame([], $this->touch->touched);

        $this->runAction(['selects' => $ids, 'confirmed' => '1', 'confirm_count' => '2'])->assertRedirect();
        $this->assertSame($ids, $this->touch->touched);
    }

    public function testAFailingActionReportsAnErrorAndLogsIt(): void
    {
        $this->touch->fails = true;

        $response = $this->runAction(['selects' => [$this->createUser()->id]]);

        $response->assertSessionHas('error', lang('Bonfire.unknownError'));
        $this->assertLogged('error', 'Touch failed');
    }
}
