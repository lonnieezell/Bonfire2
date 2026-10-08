<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Widgets;

use Bonfire\Widgets\DashboardContext;
use Bonfire\Widgets\ItemSettings;
use Bonfire\Widgets\WidgetKind;
use Tests\Support\Libraries\StubDashboard;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class DashboardContextTest extends TestCase
{
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();

        setting()->flush();
    }

    public function testIsNotTheDashboardOnAnyOtherPage()
    {
        $this->assertFalse((new DashboardContext())->isDashboard());
    }

    public function testIsTheDashboardAtTheAdminRoot()
    {
        $app      = config('App');
        $original = $app->baseURL;
        $uri      = service('request')->getUri();

        try {
            $uri->setPath(ADMIN_AREA);
            $app->baseURL = substr(current_url(), 0, -strlen('/' . ADMIN_AREA));
            $context      = new DashboardContext();

            $this->assertTrue($context->isDashboard());

            $uri->setPath(ADMIN_AREA . '/users');
            $this->assertFalse($context->isDashboard());
        } finally {
            $app->baseURL = $original;
        }
    }

    public function testShowsAnEnabledItemOnTheDashboard()
    {
        $settings = new ItemSettings(WidgetKind::Stats, 'users1');
        $settings->store(true);
        StubDashboard::inject(true);

        $this->assertTrue(service('dashboardContext')->shows($settings));
    }

    public function testDoesNotShowADisabledItem()
    {
        StubDashboard::inject(true);

        $this->assertFalse(service('dashboardContext')->shows(new ItemSettings(WidgetKind::Stats, 'users1')));
    }

    public function testDoesNotShowAnEnabledItemElsewhere()
    {
        $settings = new ItemSettings(WidgetKind::Stats, 'users1');
        $settings->store(true);
        StubDashboard::inject(false);

        $this->assertFalse(service('dashboardContext')->shows($settings));
    }
}
