<?php

namespace Tests\Theme;

use Bonfire\Users\User;
use Tests\Support\TestCase;

/**
 * The admin chrome around every page: sidebar, header and alerts.
 *
 * @internal
 */
final class AdminShellTest extends TestCase
{
    protected $refresh = true;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createUser(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
        $this->admin->addGroup('superadmin');
    }

    public function testBreadcrumbNamesTheCollectionAndThePage(): void
    {
        $response = $this->actingAs($this->admin)->get(ADMIN_AREA . '/settings/general');

        $response->assertOK();
        $this->assertBreadcrumb($response->response()->getBody(), 'Settings', 'General');
    }

    public function testBreadcrumbOnAnotherSettingsTabNamesThatTab(): void
    {
        $response = $this->actingAs($this->admin)->get(ADMIN_AREA . '/settings/users');

        $response->assertOK();
        $this->assertBreadcrumb($response->response()->getBody(), 'Settings', 'Users');
    }

    public function testBreadcrumbOnUserEditNamesTheUser(): void
    {
        $response = $this->actingAs($this->admin)->get(ADMIN_AREA . '/users/' . $this->admin->id);

        $response->assertOK();
        $this->assertBreadcrumb($response->response()->getBody(), 'Users', 'Ada Lovelace');
    }

    public function testBreadcrumbOnLogViewNamesTheLogsPageAndTheFile(): void
    {
        $logFile = WRITEPATH . 'logs/log-2000-01-01.log';
        file_put_contents($logFile, "ERROR - 2000-01-01 10:00:00 --> Log error test\n");

        try {
            $response = $this->actingAs($this->admin)->get(route_to('view-log', 'log-2000-01-01'));
        } finally {
            unlink($logFile);
        }

        $response->assertOK();
        $this->assertBreadcrumb($response->response()->getBody(), 'Logs', '2000-01-01');
    }

    public function testBreadcrumbOnSearchResultsNamesThePage(): void
    {
        $response = $this->actingAs($this->admin)->post(ADMIN_AREA . '/search', ['search_term' => 'ada']);

        $response->assertOK();
        $this->assertMatchesRegularExpression(
            '#<nav class="bf-crumbs" aria-label="Breadcrumb">\s*<ol>\s*<li aria-current="page">' . lang('Bonfire.searchResults') . '</li>#',
            $response->response()->getBody(),
        );
    }

    public function testHeaderKeepsTheSearchFormWorkingWithoutJavaScript(): void
    {
        $html = $this->actingAs($this->admin)->get(ADMIN_AREA)->response()->getBody();

        $this->assertMatchesRegularExpression(
            '#<form[^>]*action="' . preg_quote(url_to('search'), '#') . '"[^>]*method="post"#',
            $html,
        );
        $this->assertStringContainsString('name="search_term"', $html);
    }

    public function testUserMenuShowsWhoIsSignedInAndTheirActions(): void
    {
        $response = $this->actingAs($this->admin)->get(ADMIN_AREA);

        $response->assertSee('Ada Lovelace', '#user-menu');
        $response->assertSee($this->admin->email, '#user-menu');
        $response->assertSeeLink(lang('Bonfire.myAccount'));
        $response->assertSeeLink(lang('Bonfire.signOut'));
        $this->assertStringContainsString('onclick="bfTheme.toggle()"', $response->response()->getBody());
    }

    public function testSidebarShowsOnlyTheItemsTheUserMaySee(): void
    {
        // May view logs but not system info.
        $user = $this->createUser();
        $user->addPermission('admin.access', 'logs.view');

        $sidebar = $this->sidebarOf($this->actingAs($user)->get(ADMIN_AREA . '/tools/logs')->response()->getBody());

        $this->assertStringContainsString('href="' . url_to('sys-logs') . '"', $sidebar);
        $this->assertStringNotContainsString('href="' . url_to('sys-info') . '"', $sidebar);
        $this->assertStringContainsString(lang('Bonfire.toolsModTitle'), $sidebar);
    }

    public function testSidebarMarksTheCurrentPage(): void
    {
        $sidebar = $this->sidebarOf($this->actingAs($this->admin)->get(ADMIN_AREA . '/tools/logs')->response()->getBody());

        $this->assertMatchesRegularExpression('#<a[^>]*href="' . preg_quote(url_to('sys-logs'), '#') . '"[^>]*aria-current="page"#', $sidebar);
    }

    public function testFlashedAlertsShowAsDismissibleToasts(): void
    {
        $response = $this->actingAs($this->admin)->withSession(['message' => 'Saved the thing'])->get(ADMIN_AREA);

        $response->assertSee('Saved the thing', '#alerts-wrapper');
        $this->assertMatchesRegularExpression('#<aside id="alerts-wrapper"[^>]*aria-live="polite"#', $response->response()->getBody());
        $this->assertStringContainsString('data-bs-dismiss="alert"', $response->response()->getBody());
    }

    public function testOfflineBannerLinksToTheSettingThatTurnsItOff(): void
    {
        setting('Site.siteOnline', false);

        try {
            $response = $this->actingAs($this->admin)->get(ADMIN_AREA);
        } finally {
            setting('Site.siteOnline', true);
        }

        $response->assertSee(lang('Bonfire.offlineNotice'));
        $this->assertStringContainsString('href="' . site_url(ADMIN_AREA . '/settings/general') . '"', $response->response()->getBody());
    }

    private function sidebarOf(string $html): string
    {
        $this->assertSame(1, preg_match('#<aside id="sidebar".*?</aside>#s', $html, $match), 'No sidebar found.');

        return $match[0];
    }

    private function assertBreadcrumb(string $html, string $parent, string $current): void
    {
        $this->assertMatchesRegularExpression(
            '#<nav class="bf-crumbs" aria-label="Breadcrumb">\s*<ol>\s*<li>' . preg_quote($parent, '#')
            . '</li>\s*<li aria-current="page">' . preg_quote($current, '#') . '</li>\s*</ol>#',
            $html,
        );
    }
}
