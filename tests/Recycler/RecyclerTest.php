<?php

namespace Tests\Recycler;

use Bonfire\Users\Models\UserModel;
use Bonfire\Users\User;
use Bonfire\Users\UserPersonalData;
use CodeIgniter\Exceptions\PageNotFoundException;
use Tests\Support\Models\RecyclerHooksModel;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class RecyclerTest extends TestCase
{
    protected $refresh = true;
    private User $admin;
    private UserModel $users;

    protected function setUp(): void
    {
        parent::setUp();

        // Other tests may leave the site in offline mode, which redirects users without site.viewOffline
        setting('Site.siteOnline', true);

        $this->admin = $this->createUser();
        $this->admin->addGroup('superadmin');

        $this->users = new UserModel();
    }

    public function testIndexShowsDeletedUsers()
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();

        $this->users->delete($user1->id);

        $response = $this->actingAs($this->admin)
            ->get(route_to('recycler'));

        $response->assertOK();
        $response->assertSee($user1->first_name);
        $response->assertDontSee($user2->first_name);
    }

    public function testErasedUsersAreNotListed(): void
    {
        $deleted = $this->createUser(['username' => 'deleted-one']);
        $erased  = $this->createUser(['username' => 'erased-one']);
        $this->users->delete($deleted->id);
        $this->eraseUser($erased);

        $response = $this->actingAs($this->admin)->get(route_to('recycler') . '?r=users');

        $response->assertSee('deleted-one');
        $response->assertDontSee('deleted-user-' . $erased->id);
        $this->assertSame(1, service('recycler')->find('users')->deletedCount());
    }

    public function testErasedUsersCannotBeRestoredOrPurged(): void
    {
        $erased = $this->createUser();
        $this->eraseUser($erased);

        $this->actingAs($this->admin)->post(route_to('recycler-restore', 'users', $erased->id), [csrf_token() => csrf_hash()]);
        $this->actingAs($this->admin)->post(route_to('recycler-purge', 'users', $erased->id), [csrf_token() => csrf_hash()]);

        $this->assertNull($this->users->find($erased->id));
        $this->seeInDatabase('users', ['id' => $erased->id]);
    }

    public function testRestore()
    {
        $user1 = $this->createUser();
        $this->users->delete($user1->id);

        $this->actingAs($this->admin)
            ->post(route_to('recycler-restore', 'users', $user1->id), [csrf_token() => csrf_hash()]);

        $this->seeInDatabase('users', [
            'id'         => $user1->id,
            'deleted_at' => null,
        ]);
    }

    public function testPurge()
    {
        $user1 = $this->createUser();
        $this->users->delete($user1->id);

        $this->actingAs($this->admin)
            ->post(route_to('recycler-purge', 'users', $user1->id), [csrf_token() => csrf_hash()]);

        $this->dontSeeInDatabase('users', [
            'id' => $user1->id,
        ]);
    }

    private function eraseUser(User $user): void
    {
        service('personalData')->register('users', new UserPersonalData())->erase($user);
    }

    private function userWithoutRecyclerAccess(): User
    {
        $user = $this->createUser();
        $user->addPermission('admin.access');

        return $user;
    }

    public function testIndexNeedsRecyclerPermission()
    {
        $deleted = $this->createUser();
        $this->users->delete($deleted->id);

        $response = $this->actingAs($this->userWithoutRecyclerAccess())
            ->get(route_to('recycler'));

        $response->assertRedirectTo(ADMIN_AREA);
        $response->assertDontSee($deleted->first_name);
    }

    public function testRestoreNeedsRecyclerPermission()
    {
        $deleted = $this->createUser();
        $this->users->delete($deleted->id);

        $this->actingAs($this->userWithoutRecyclerAccess())
            ->post(route_to('recycler-restore', 'users', $deleted->id), [csrf_token() => csrf_hash()])
            ->assertRedirectTo(ADMIN_AREA);

        $this->assertNotNull(db_connect()->table('users')->where('id', $deleted->id)->get()->getRow()->deleted_at);
    }

    public function testPurgeNeedsRecyclerPermission()
    {
        $deleted = $this->createUser();
        $this->users->delete($deleted->id);

        $this->actingAs($this->userWithoutRecyclerAccess())
            ->post(route_to('recycler-purge', 'users', $deleted->id), [csrf_token() => csrf_hash()])
            ->assertRedirectTo(ADMIN_AREA);

        $this->seeInDatabase('users', ['id' => $deleted->id]);
    }

    public function testUnknownResourceIsNotListed()
    {
        $response = $this->actingAs($this->admin)
            ->get(route_to('recycler') . '?r=nothing');

        $response->assertRedirect();
        $this->assertSame(lang('Bonfire.resourceNotFound', [lang('Recycler.resourceType')]), session('error'));
    }

    public function testUnknownResourceIsNotRestoredOrPurged()
    {
        $deleted = $this->createUser();
        $this->users->delete($deleted->id);

        foreach (['recycler-restore', 'recycler-purge'] as $route) {
            $response = $this->actingAs($this->admin)
                ->post(route_to($route, 'nothing', $deleted->id), [csrf_token() => csrf_hash()]);

            $response->assertRedirect();
            $this->assertSame(lang('Bonfire.resourceNotFound', [lang('Recycler.resourceType')]), session('error'));
        }

        $this->seeInDatabase('users', ['id' => $deleted->id]);
    }

    public function testRestoreOfUnknownRecordReportsNothing()
    {
        $response = $this->actingAs($this->admin)
            ->post(route_to('recycler-restore', 'users', 999999), [csrf_token() => csrf_hash()]);

        $response->assertRedirect();
        $this->assertNull(session('message'));
    }

    public function testCustomHooksAreUsedByTheController()
    {
        service('recycler')->register('hooks', 'Hooks', RecyclerHooksModel::class, ['username']);
        RecyclerHooksModel::$calls = [];

        $deleted = $this->createUser();
        $this->users->delete($deleted->id);

        $this->actingAs($this->admin)->get(route_to('recycler') . '?r=hooks');
        $this->actingAs($this->admin)->post(route_to('recycler-restore', 'hooks', $deleted->id), [csrf_token() => csrf_hash()]);
        $this->actingAs($this->admin)->post(route_to('recycler-purge', 'hooks', $deleted->id), [csrf_token() => csrf_hash()]);

        $this->assertSame(['query', "restore {$deleted->id}", "purge {$deleted->id}"], RecyclerHooksModel::$calls);
    }

    public function testAnIntegerKeyedResourceGetsOnlyDigitsAsKeys(): void
    {
        service('recycler')->register('hooks', 'Hooks', RecyclerHooksModel::class, ['username']);
        RecyclerHooksModel::$calls = [];

        $deleted = $this->createUser();
        $this->users->delete($deleted->id);

        $this->actingAs($this->admin)->post(route_to('recycler-restore', 'hooks', "{$deleted->id}abc"), [csrf_token() => csrf_hash()]);
        $this->actingAs($this->admin)->post(route_to('recycler-purge', 'hooks', "{$deleted->id}abc"), [csrf_token() => csrf_hash()]);

        $this->assertSame([], RecyclerHooksModel::$calls);
    }

    public function testListShowsTheCountAndRowsWithRestoreAndAConfirmedPurge(): void
    {
        $user = $this->createUser();
        $this->users->delete($user->id);

        // The raw body: the DOM-parsed getBody() drops Alpine's @-attributes.
        $html = (string) $this->actingAs($this->admin)->get(route_to('recycler'))->response()->getBody();

        $this->assertMatchesRegularExpression('#<h1>Recycler</h1>\s*<span class="bf-count">1</span>#', $html);
        $token = 'name="' . csrf_token() . '"';
        $this->assertMatchesRegularExpression('#<form action="' . preg_quote(url_to('recycler-restore', 'users', $user->id), '#') . '" method="post">\s*<input type="hidden" ' . $token . '#', $html);
        $this->assertMatchesRegularExpression('#<form action="' . preg_quote(url_to('recycler-purge', 'users', $user->id), '#') . '" method="post">\s*<input type="hidden" ' . $token . '[^>]*>\s*<button type="submit" class="btn btn-sm btn-outline-danger"\s+data-confirm="Permanently delete this record\?" data-confirm-button="Yes, purge" data-confirm-danger="1">#', $html);
        $this->assertStringNotContainsString('<a href="' . url_to('recycler-restore', 'users', $user->id), $html);
        $this->assertStringNotContainsString('<a href="' . url_to('recycler-purge', 'users', $user->id), $html);
        $this->assertStringNotContainsString('confirm(', $html);
    }

    public function testResourceChooserSwapsTheRecyclerAndStartsOnTheFirstPage(): void
    {
        service('recycler')->register('hooks', 'Hooks', RecyclerHooksModel::class, ['username']);

        // The raw body: the DOM-parsed getBody() drops Alpine's @-attributes.
        $html = (string) $this->actingAs($this->admin)->get(route_to('recycler') . '?page=2')->response()->getBody();

        $this->assertStringContainsString('<div id="recycler">', $html);
        $this->assertSame(1, preg_match('#<select\b[^>]*\bname="r"[^>]*>#', $html, $matches));

        $select = $matches[0];
        $this->assertStringContainsString('id="recycler-resource"', $select);
        $this->assertStringContainsString('hx-get="' . url_to('recycler') . '"', $select);
        $this->assertStringContainsString('hx-target="#recycler"', $select);
        $this->assertStringContainsString('hx-select="#recycler"', $select);
        $this->assertStringContainsString('hx-swap="outerHTML"', $select);
        $this->assertStringContainsString('hx-push-url="true"', $select);
        $this->assertStringNotContainsString('x-on:change', $select);
    }

    public function testEmptyRecyclerShowsAnEmptyState(): void
    {
        $html = $this->actingAs($this->admin)->get(route_to('recycler'))->getBody();

        $this->assertStringContainsString('<div class="bf-empty">', $html);
    }

    public function testAGetToRestoreOrPurgeChangesNothing(): void
    {
        $user = $this->createUser();
        $this->users->delete($user->id);

        foreach (['restore', 'purge'] as $action) {
            try {
                $this->actingAs($this->admin)->get(ADMIN_AREA . "/tools/recycler/{$action}/users/{$user->id}");
            } catch (PageNotFoundException) {
                // What it should do. The record is what matters.
            }
        }

        $this->assertNull($this->users->find($user->id));
        $this->seeInDatabase('users', ['id' => $user->id]);
    }

    public function testRestoreAndPurgeNeedACsrfToken(): void
    {
        $user = $this->createUser();
        $this->users->delete($user->id);

        foreach (['recycler-restore', 'recycler-purge'] as $route) {
            $this->actingAs($this->admin)->post(route_to($route, 'users', $user->id))->assertRedirect();
        }

        $this->assertNull($this->users->find($user->id));
        $this->seeInDatabase('users', ['id' => $user->id]);
    }
}
