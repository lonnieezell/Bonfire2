<?php

namespace Tests\Settings;

use Bonfire\Users\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\TestCase;

/**
 * Every settings page shares one frame: the "Settings" head and a sub-nav of the settings pages.
 *
 * @internal
 */
final class SettingsLayoutTest extends TestCase
{
    protected $refresh = true;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createUser(['active' => 1]);
        $this->admin->addGroup('superadmin');

        // Other tests leave a changed matrix, or the site offline, in the settings cache.
        setting('AuthGroups.matrix', config('AuthGroups')->matrix);
        setting('Site.siteOnline', true);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function settingsPages(): array
    {
        return [
            'general'     => ['admin/settings/general', 'General'],
            'users'       => ['admin/settings/users', 'Users'],
            'groups'      => ['admin/settings/groups', 'User Groups'],
            'group'       => ['admin/settings/groups/admin', 'User Groups'],
            'permissions' => ['admin/settings/groups/admin/permissions', 'User Groups'],
            'email'       => ['admin/settings/email', 'Email'],
            'consent'     => ['admin/settings/consent', 'Consent'],
            'widgets'     => ['admin/settings/widgets', 'Widgets'],
        ];
    }

    #[DataProvider('settingsPages')]
    public function testPageSitsInTheSettingsFrameWithItsNavItemActive(string $path, string $navTitle): void
    {
        $result = $this->actingAs($this->admin)->get($path);
        $result->assertOK();
        $html = $result->getBody();

        $this->assertMatchesRegularExpression('#<h1>Settings</h1>#', $html);
        $this->assertMatchesRegularExpression('#<nav class="bf-settings-nav"[^>]*>.*?</nav>\s*<div class="bf-settings-body">#s', $html);
        $this->assertMatchesRegularExpression('#<a class="bf-settings-nav-link active" href="[^"]+" aria-current="page">\s*(<i[^>]*></i>\s*)?' . preg_quote($navTitle, '#') . '\s*</a>#', $html);
        $this->assertSame(1, substr_count($html, 'bf-settings-nav-link active'));
    }

    public function testSubNavOnlyListsPagesTheUserMayOpen(): void
    {
        $user = $this->createUser(['active' => 1]);
        $user->addPermission('admin.access', 'widgets.settings');

        $html = $this->actingAs($user)->get('admin/settings/widgets')->getBody();

        $this->assertStringContainsString('href="' . site_url('admin/settings/widgets') . '"', $html);
        $this->assertStringNotContainsString('href="' . site_url('admin/settings/general') . '"', $html);
        $this->assertStringNotContainsString('href="' . site_url('admin/settings/email') . '"', $html);
    }
}
