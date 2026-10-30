<?php

namespace Tests\Theme;

use Bonfire\Menus\MenuItem;
use Bonfire\Users\User;
use Config\Services;
use Tests\Support\Packages\Courier\Module as Courier;
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

        // Other tests may leave the site offline, which redirects users without site.viewOffline.
        setting('Site.siteOnline', true);
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
        // Other tests may leave a different date format behind.
        setting('App.dateFormat', 'Y-m-d');
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
        $this->assertStringContainsString('name="search_term"', (string) $html);
    }

    public function testUserMenuShowsWhoIsSignedInAndTheirActions(): void
    {
        $response = $this->actingAs($this->admin)->get(ADMIN_AREA);

        $response->assertSee('Ada Lovelace', '#user-menu');
        $response->assertSee($this->admin->email, '#user-menu');
        $response->assertSeeLink(lang('Bonfire.myAccount'));
        $response->assertSeeLink(lang('Bonfire.signOut'));
        $this->assertStringContainsString('onclick="bfTheme.toggle()"', (string) $response->response()->getBody());
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

    public function testSidebarLinksToSettingsOnceAndMarksItActive(): void
    {
        $sidebar = $this->sidebarOf($this->actingAs($this->admin)->get(ADMIN_AREA . '/settings/users')->response()->getBody());

        // The settings pages have their own nav, so the sidebar shows one link to them.
        $this->assertSame(1, preg_match_all('#<a[^>]*href="' . preg_quote(site_url(ADMIN_AREA . '/settings/'), '#') . '#', $sidebar));
        $this->assertMatchesRegularExpression('#<a class="bf-nav-link active"[^>]*href="' . preg_quote(site_url(ADMIN_AREA . '/settings/'), '#') . '#', $sidebar);
    }

    public function testSidebarSettingsLinkOpensTheFirstSettingsPageTheUserMaySee(): void
    {
        $user = $this->createUser();
        $user->addPermission('admin.access', 'users.settings');

        $sidebar = $this->sidebarOf($this->actingAs($user)->get(ADMIN_AREA)->response()->getBody());

        $this->assertStringContainsString('href="' . url_to('user-settings') . '"', $sidebar);
    }

    public function testItemAddedOutsideACollectionShowsInTheSidebarPaletteAndBreadcrumb(): void
    {
        $url = ADMIN_AREA . '/users/' . $this->admin->id;

        // Register the item from a module's initAdmin(), as the menu is rebuilt on every admin request.
        Services::resetSingle('bonfire');
        config('Bonfire')->packageModules = ['Tests\Support\Packages\Courier' => SUPPORTPATH . 'Packages/Courier'];
        Courier::$onInitAdmin             = static fn () => service('menus')->menu('sidebar')
            ->addItem(new MenuItem(['title' => 'My Profile', 'url' => $url]));

        try {
            $html = (string) $this->actingAs($this->admin)->get($url)->response()->getBody();
        } finally {
            Courier::$onInitAdmin             = null;
            config('Bonfire')->packageModules = [];
            Services::resetSingle('bonfire');
        }

        $this->assertMatchesRegularExpression('#<a[^>]*href="' . preg_quote(site_url($url), '#') . '"[^>]*aria-current="page"#', $this->sidebarOf($html));
        $this->assertStringContainsString('"label":"My Profile"', $html);
        $this->assertBreadcrumb($html, 'My Profile', 'Ada Lovelace');
    }

    public function testFlashedAlertsShowAsDismissibleToasts(): void
    {
        $response = $this->actingAs($this->admin)->withSession(['message' => 'Saved the thing'])->get(ADMIN_AREA);

        $response->assertSee('Saved the thing', '#alerts-wrapper');
        $this->assertMatchesRegularExpression('#<aside id="alerts-wrapper"[^>]*aria-live="polite"#', $response->response()->getBody());
        $this->assertStringContainsString('data-bs-dismiss="alert"', (string) $response->response()->getBody());
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
        $this->assertStringContainsString('href="' . site_url(ADMIN_AREA . '/settings/general') . '"', (string) $response->response()->getBody());
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
