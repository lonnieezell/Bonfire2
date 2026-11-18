<?php

namespace Tests\Support\Media;

use Bonfire\Users\User;
use Config\Services;

/**
 * A MediaTestCase for the admin screens: the site online, the routes loaded, storage that moves test uploads, and a
 * superadmin in `$admin`.
 *
 * @internal
 */
abstract class MediaAdminTestCase extends MediaTestCase
{
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        setting('Site.siteOnline', true);

        foreach (['auth', 'bonfire', 'menus', 'widgets'] as $service) {
            Services::resetSingle($service);
        }

        service('routes')->loadRoutes();

        Services::injectMock('mediaStorage', $this->storage());

        $this->admin = $this->createUser();
        $this->admin->addGroup('superadmin');
    }

    protected function tearDown(): void
    {
        service('superglobals')->setFilesArray([]);

        foreach (['auth', 'bonfire', 'menus', 'widgets', 'mediaStorage'] as $service) {
            Services::resetSingle($service);
        }

        parent::tearDown();
    }
}
