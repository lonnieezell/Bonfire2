<@php

namespace Tests\Feature;

use Tests\Support\AdminTestCase;

/**
 * @internal
 */
final class <?= $name ?>ModuleTest extends AdminTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        command('bonfire:permissions');
    }

    public function testAdminSeesTheList(): void
    {
        $result = $this->actingAs($this->createUser(['admin']))->get(ADMIN_AREA . '/<?= $slug ?>');

        $result->assertOK();
    }

    public function testAdminSeesTheNewForm(): void
    {
        $result = $this->actingAs($this->createUser(['admin']))->get(ADMIN_AREA . '/<?= $slug ?>/new');

        $result->assertOK();
    }

    public function testUserWithoutThePermissionIsSentToTheDashboard(): void
    {
        $result = $this->actingAs($this->createUser(['developer']))->get(ADMIN_AREA . '/<?= $slug ?>');

        $result->assertRedirectTo(site_url(ADMIN_AREA));
    }

    public function testGuestIsSentToTheLoginPage(): void
    {
        $result = $this->get(ADMIN_AREA . '/<?= $slug ?>');

        $result->assertRedirectTo(url_to('login'));
    }
}
