<?php

namespace Tests\Users;

use Bonfire\Users\Models\UserModel;
use Bonfire\Users\User;
use CodeIgniter\Database\Query;
use CodeIgniter\Events\Events;
use CodeIgniter\Shield\Authentication\Actions\EmailActivator;
use Config\Services;
use Tests\Support\Actions\TouchUsers;
use Tests\Support\TestCase;

/**
 * The users list: rows, toolbar filters, selection and pager.
 *
 * @internal
 */
final class UserListTest extends TestCase
{
    protected $refresh = true;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createUser(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'username' => 'ada', 'active' => 1]);
        $this->admin->addGroup('superadmin');
    }

    public function testHeadShowsTheTotalAndTheNewUserButton(): void
    {
        $this->createUser(['username' => 'grace', 'active' => 1])->addGroup('user');

        $html = $this->list();

        $this->assertMatchesRegularExpression('#<h1>Users</h1>\s*<span class="bf-count">2</span>#', $html);
        $this->assertMatchesRegularExpression('#<div class="bf-page-head-actions">\s*<a href="' . preg_quote(url_to('user-new'), '#') . '" class="btn btn-primary">#', $html);
    }

    public function testRowsShowNameEmailUsernameGroupsAndStatus(): void
    {
        // Users are only "pending" while registration needs email activation.
        setting('Auth.actions', ['register' => EmailActivator::class, 'login' => null]);
        $pending = $this->createUser(['first_name' => 'Grace', 'last_name' => 'Hopper', 'username' => 'grace', 'active' => 0]);
        $pending->addGroup('user');
        $banned = $this->createUser(['username' => 'mallory', 'active' => 1]);
        $banned->ban();

        $html = $this->list();

        $this->assertStringContainsString('Ada Lovelace', $html);
        $this->assertStringContainsString(esc($this->admin->email), $html);
        $this->assertStringContainsString('<td class="bf-mono">ada</td>', $html);
        $this->assertMatchesRegularExpression('#<span class="bf-pill">Super Admin</span>#', $html);
        $this->assertMatchesRegularExpression('#bf-status-ok"><span class="bf-dot" aria-hidden="true"></span>Active#', $html);
        $this->assertMatchesRegularExpression('#bf-status-warn"><span class="bf-dot" aria-hidden="true"></span>Pending#', $html);
        $this->assertMatchesRegularExpression('#bf-status-danger"><span class="bf-dot" aria-hidden="true"></span>Banned#', $html);
    }

    public function testSearchFiltersByNameEmailOrUsername(): void
    {
        $this->createUser(['first_name' => 'Grace', 'username' => 'grace', 'active' => 1]);

        $html = $this->list('?search=grac', htmx: true);

        $this->assertStringContainsString('<td class="bf-mono">grace</td>', $html);
        $this->assertStringNotContainsString('<td class="bf-mono">ada</td>', $html);
    }

    public function testGroupChipsFilterAndShowCounts(): void
    {
        $this->createUser(['username' => 'grace', 'active' => 1])->addGroup('user');
        $this->createUser(['username' => 'linus', 'active' => 1])->addGroup('user');

        $page = $this->list();
        $this->assertMatchesRegularExpression('#<input type="radio" name="role" value="user"\s*>\s*User\s*<span class="bf-chip-count">2</span>#', $page);
        $this->assertMatchesRegularExpression('#<input type="radio" name="role" value="" checked>\s*All\s*<span class="bf-chip-count">3</span>#', $page);

        $filtered = $this->list('?role=user', htmx: true);
        $this->assertStringContainsString('<td class="bf-mono">grace</td>', $filtered);
        $this->assertStringNotContainsString('<td class="bf-mono">ada</td>', $filtered);
    }

    public function testGroupTitlesCannotInjectAttributesIntoTheChips(): void
    {
        $groups         = setting('AuthGroups.groups');
        $groups['evil'] = ['title' => 'x" onfocus="alert(1)', 'description' => ''];
        setting('AuthGroups.groups', $groups);

        $html = $this->list();

        $this->assertStringNotContainsString('onfocus="alert(1)"', $html);
        $this->assertStringContainsString('x&quot; onfocus=&quot;alert(1)', $html);
    }

    public function testNoMatchesShowAnEmptyState(): void
    {
        $html = $this->list('?search=nobody-like-this', htmx: true);

        $this->assertStringContainsString('<div class="bf-empty-title">No users match</div>', $html);
    }

    public function testRowsAreSelectableForAnyBulkActionTheAdminMayRun(): void
    {
        $editor = $this->createUser(['username' => 'editor', 'active' => 1]);
        $editor->addPermission('admin.access', 'users.list', 'users.edit');
        $grace = $this->createUser(['username' => 'grace', 'active' => 1]);

        $this->admin = $editor;
        $this->assertDoesNotMatchRegularExpression('#name="selects\[\]"#', $this->list());

        service('actions')->for('users')->add(new TouchUsers());

        try {
            $html = $this->list();
        } finally {
            Services::resetSingle('actions');
        }

        $this->assertStringContainsString('x-data="bfSelectionBar"', $html);
        $this->assertMatchesRegularExpression('#<input type="checkbox" name="selects\[\]" value="' . $grace->id . '"[^>]*\s+>#', $html);
        // TouchUsers refuses the editor's own record.
        $this->assertMatchesRegularExpression('#name="selects\[\]" value="' . $editor->id . '"[^>]*\s+disabled#', $html);
    }

    public function testRowActionsDoNotQueryPerRow(): void
    {
        $this->createUser(['username' => 'grace', 'active' => 1]);
        $this->list(htmx: true); // loads the admin's own permissions once
        $twoRows = $this->countQueries(fn () => $this->list(htmx: true));

        $this->createUser(['username' => 'linus', 'active' => 1]);
        $this->createUser(['username' => 'ken', 'active' => 1]);
        $fourRows = $this->countQueries(fn () => $this->list(htmx: true));

        $this->assertSame($twoRows, $fourRows);
    }

    public function testDeleteActionDeletesTheSelectedUsersButNotYourself(): void
    {
        $grace = $this->createUser(['username' => 'grace', 'active' => 1]);

        $response = $this->actingAs($this->admin)->post(route_to('action', 'users', 'delete-users'), [
            csrf_token() => csrf_hash(),
            'selects'    => [$grace->id, $this->admin->id],
            'confirmed'  => '1',
        ]);

        $response->assertSessionHas('message', lang('Bonfire.resourcesDeleted', [lang('Users.usersAccusative')]) . ' Skipped 1.');
        $this->assertNull(model(UserModel::class)->find($grace->id));
        $this->assertNotNull(model(UserModel::class)->find($this->admin->id));
    }

    public function testTheOldDeleteRoutesAreGone(): void
    {
        $this->assertFalse(route_to('user-delete', $this->admin->id));
        $this->assertNull(service('routes')->getRoutes('POST')[ADMIN_AREA . '/users/delete-batch'] ?? null);
    }

    public function testPagerShowsHowManyOfTheTotal(): void
    {
        setting('Site.perPage', 1);
        $this->createUser(['username' => 'grace', 'active' => 1]);

        $html = $this->list(htmx: true);

        $this->assertStringContainsString('Showing 1 of 2', $html);
        $this->assertMatchesRegularExpression('#<a class="btn[^"]*" href="[^"]*page=2[^"]*" hx-get="[^"]*page=2#', $html);
    }

    /**
     * Counts the SELECTs, leaving out writes like the session's random expiry purge.
     */
    private function countQueries(callable $request): int
    {
        $count    = 0;
        $listener = static function (Query $query) use (&$count): void {
            if (str_starts_with($query->getQuery(), 'SELECT')) {
                $count++;
            }
        };
        Events::on('DBQuery', $listener);

        try {
            $request();
        } finally {
            Events::removeListener('DBQuery', $listener);
        }

        return $count;
    }

    private function list(string $query = '', bool $htmx = false): string
    {
        $request = $this->actingAs($this->admin);

        if ($htmx) {
            $request = $request->withHeaders(['HX-Request' => 'true']);
        }

        $response = $request->get(ADMIN_AREA . '/users' . $query);
        $response->assertOK();

        return $response->response()->getBody();
    }
}
