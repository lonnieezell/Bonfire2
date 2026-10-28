<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Dashboard;

use Bonfire\Users\User;
use Bonfire\Widgets\Types\Stats\StatsItem;
use Config\Services;
use Tests\Support\Libraries\StubDashboard;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class CustomizeDashboardTest extends TestCase
{
    private const QUICK_LINKS = 'cell:Bonfire\Dashboard\DashboardCells::quickLinks';

    protected $refresh = true;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        setting()->flush();
        Services::resetSingle('dashboard');
        setting('Stats.Stats_usersInRecycler693', 'on');
        setting('Stats.Stats_userGroups643', 'on');
        $this->user = $this->admin();
    }

    protected function tearDown(): void
    {
        Services::resetSingle('viewMeta');
        Services::resetSingle('dashboardContext');
        setting()->flush();

        parent::tearDown();
    }

    public function testTheDashboardOffersTheEditorItsLayoutAndSaveUrl(): void
    {
        $html = $this->dashboard($this->user);

        $this->assertMatchesRegularExpression('#<div class="bf-dashboard"[^>]*x-data="dashboardEditor"#', $html);
        $this->assertStringContainsString('data-save-url="' . url_to('dashboard-layout') . '"', $html);
        $this->assertMatchesRegularExpression('#<button[^>]*class="bf-widget-handle[^"]*"#', $html);
        $this->assertMatchesRegularExpression('#<script[^>]+src="https://cdn\.jsdelivr\.net/npm/sortablejs@1\.15\.6/Sortable\.min\.js"[^>]+integrity="sha256-#', $html);
    }

    public function testSavingStoresTheOrderForThatUserOnly(): void
    {
        $other = $this->admin();

        $this->save($this->user, [['title' => '', 'widgets' => [
            ['id' => 'stats:usersInRecycler693', 'size' => 'sm'],
            ['id' => self::QUICK_LINKS, 'size' => 'full'],
        ]]])->assertOK();

        $this->assertSame(['stats:usersInRecycler693', self::QUICK_LINKS], $this->renderedIds($this->user));
        $this->assertSame([self::QUICK_LINKS, 'stats:userGroups643', 'stats:usersInRecycler693'], $this->renderedIds($other));
    }

    public function testSavingDropsUnknownUnavailableAndDuplicateIds(): void
    {
        $this->save($this->user, [['title' => '', 'widgets' => [
            ['id' => 'stats:usersInRecycler693', 'size' => 'sm'],
            ['id' => 'stats:nope', 'size' => 'sm'],
            ['id' => 'stats:usersExpTable693', 'size' => 'sm'],
            ['id' => 'stats:usersInRecycler693', 'size' => 'sm'],
        ]]])->assertOK();

        $this->assertSame(['stats:usersInRecycler693'], $this->renderedIds($this->user));
    }

    public function testSectionsAndSizesPersistAndRender(): void
    {
        $this->save($this->user, [
            ['title' => 'People <b>', 'widgets' => [['id' => 'stats:usersInRecycler693', 'size' => 'lg']]],
            ['title' => '', 'widgets' => [['id' => 'stats:userGroups643', 'size' => 'sm']]],
        ])->assertOK();

        $html = $this->dashboard($this->user);

        $this->assertMatchesRegularExpression('#<section class="bf-dashboard-section"[^>]*>\s*<h2 class="bf-dashboard-section-title"[^>]*>People &lt;b&gt;</h2>#', $html);
        $this->assertSame(1, substr_count($html, 'class="bf-dashboard-section-title"'));
        $this->assertMatchesRegularExpression('#class="bf-widget bf-span-9" data-widget-id="stats&\#x3A;usersInRecycler693"#', $html);
    }

    public function testTooManySectionsAreRefused(): void
    {
        $this->save($this->user, array_fill(0, 21, ['title' => '', 'widgets' => [['id' => 'stats:usersInRecycler693']]]))
            ->assertStatus(400);
    }

    public function testTheSizeMenuOffersTheWidgetsPresets(): void
    {
        preg_match('#data-widget-id="stats&\#x3A;usersInRecycler693".*?</ul>#s', $this->dashboard($this->user), $tile);
        preg_match_all('/data-size-option="(\w+)"/', $tile[0], $options);

        $this->assertSame(['sm', 'md', 'lg', 'full'], $options[1]);

        preg_match('#data-widget-id="logs&\#x3A;logActivity".*?</ul>#s', $this->dashboard($this->logViewer()), $tile);
        preg_match_all('/data-size-option="(\w+)"/', $tile[0] ?? '', $options);

        $this->assertSame(['md', 'lg', 'full'], $options[1]);
    }

    public function testAMalformedPayloadOrWrongVersionIsRejected(): void
    {
        $this->save($this->user, 'nonsense')->assertStatus(400);
        $this->save($this->user, [], 2)->assertStatus(400);

        $this->assertNull(setting()->get('Dashboard.layout', 'user:' . $this->user->id));
    }

    public function testAStoredLayoutSkipsWidgetsThatAreGone(): void
    {
        setting()->set('Dashboard.layout', ['version' => 1, 'sections' => [['title' => '', 'widgets' => [
            ['id' => 'stats:removedModule', 'size' => 'sm'],
            ['id' => 'stats:userGroups643', 'size' => 'sm'],
            ['id' => 'stats:usersInRecycler693', 'size' => 'sm'],
        ]]]], 'user:' . $this->user->id);
        setting('Stats.Stats_userGroups643', false);

        $this->assertSame(['stats:usersInRecycler693'], $this->renderedIds($this->user));
    }

    public function testResetForgetsTheUsersLayout(): void
    {
        $this->save($this->user, [['title' => '', 'widgets' => [['id' => 'stats:usersInRecycler693', 'size' => 'sm']]]]);

        $this->actingAs($this->user)
            ->post(route_to('dashboard-layout-reset'), [csrf_token() => csrf_hash()])
            ->assertRedirectTo(site_url(ADMIN_AREA));

        $this->assertSame([self::QUICK_LINKS, 'stats:userGroups643', 'stats:usersInRecycler693'], $this->renderedIds($this->user));
    }

    public function testResetGoesThroughTheConfirmDialog(): void
    {
        $this->assertMatchesRegularExpression(
            '#<form[^>]*action="' . preg_quote(url_to('dashboard-layout-reset'), '#') . '"[^>]*>.*?<button[^>]*data-confirm=#s',
            $this->dashboard($this->user),
        );
    }

    public function testLazyQueriesRunOnlyForWidgetsInTheUsersLayout(): void
    {
        StubDashboard::inject(true);
        setting('Stats.Stats_users1', 'on');
        setting('Stats.Stats_users2', 'on');
        setting()->set('Dashboard.layout', ['version' => 1, 'sections' => [['title' => '', 'widgets' => [
            ['id' => 'stats:users1', 'size' => 'sm'],
        ]]]], 'user:' . $this->user->id);
        $this->actingAs($this->user);

        $inLayout  = (new StatsItem())->setId('users1')->addValue('users');
        $offLayout = (new StatsItem())->setId('users2')->addValue('users');

        $this->assertNotNull($inLayout->value());
        $this->assertNull($offLayout->value());
    }

    private function logViewer(): User
    {
        setting('Stats.Logs_logActivity', 'on');

        return $this->user;
    }

    private function admin(): User
    {
        $user = $this->createUser();
        $user->addGroup('superadmin');

        return $user;
    }

    /**
     * @param list<array<string, mixed>>|string $sections
     */
    private function save(User $user, array|string $sections, int $version = 1)
    {
        $body = is_string($sections) ? $sections : json_encode(['version' => $version, 'sections' => $sections]);

        return $this->actingAs($user)
            ->withHeaders([config('Security')->headerName => csrf_hash(), 'Content-Type' => 'application/json'])
            ->withBody($body)
            ->post(route_to('dashboard-layout'));
    }

    private function dashboard(User $user): string
    {
        return (string) $this->actingAs($user)->get(ADMIN_AREA)->response()->getBody();
    }

    /**
     * @return list<string>
     */
    private function renderedIds(User $user): array
    {
        Services::resetSingle('viewMeta');
        preg_match_all('/data-widget-id="([^"]+)"/', $this->dashboard($user), $matches);

        return array_map(html_entity_decode(...), $matches[1]);
    }
}
