<?php

namespace Tests\Resources;

use Bonfire\Resources\ResourceTab;
use Bonfire\Resources\ResourceTabs;
use Bonfire\Users\User;
use Config\Services;
use Tests\Support\TestCase;

/**
 * Tabs other modules add to the user and group edit pages.
 *
 * @internal
 */
final class ResourceTabsTest extends TestCase
{
    protected $refresh = true;
    private User $admin;
    private ResourceTabs $tabs;

    protected function setUp(): void
    {
        parent::setUp();

        setting('Auth.actions', ['login' => null, 'register' => null]);

        $this->admin = $this->createUser(['username' => 'ada', 'active' => 1]);
        $this->admin->addGroup('superadmin');

        $this->tabs = new ResourceTabs();
        Services::injectMock('resourceTabs', $this->tabs);
    }

    public function testUserTabLinksToTheUserBeingEdited(): void
    {
        $this->tabs->addTabFor('user', new ResourceTab(['title' => 'Extra', 'url' => 'users/(id)/security']));

        $html = $this->page('/admin/users/' . $this->admin->id);

        $href = preg_quote(site_url(ADMIN_AREA . '/users/' . $this->admin->id . '/security'), '#');
        $this->assertMatchesRegularExpression('#<a class="nav-link\s*" href="' . $href . '">\s*Extra#', $html);
    }

    public function testUserTabIsActiveOnItsOwnPage(): void
    {
        $this->tabs->addTabFor('user', new ResourceTab(['title' => 'Extra', 'url' => 'users/(id)/security']));

        $html = $this->page('/admin/users/' . $this->admin->id . '/security');

        $href = preg_quote(site_url(ADMIN_AREA . '/users/' . $this->admin->id . '/security'), '#');
        $this->assertMatchesRegularExpression('#<a class="nav-link\s+active\s*" href="' . $href . '">\s*Extra#', $html);
    }

    public function testGroupTabLinksToTheGroupBeingEdited(): void
    {
        $this->tabs->addTabFor('group', new ResourceTab(['title' => 'Extra', 'url' => 'settings/groups/(id)/extra']));

        $html = $this->page('/admin/settings/groups/admin');

        $href = preg_quote(site_url(ADMIN_AREA . '/settings/groups/admin/extra'), '#');
        $this->assertMatchesRegularExpression('#href="' . $href . '">\s*Extra#', $html);
    }

    private function page(string $path): string
    {
        $result = $this->actingAs($this->admin)->get($path);
        $result->assertOK();

        return $result->getBody();
    }
}
