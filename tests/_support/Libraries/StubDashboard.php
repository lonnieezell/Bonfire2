<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Support\Libraries;

use Bonfire\Widgets\DashboardContext;
use Config\Services;

/**
 * A dashboard context whose answer is set by the test, so widget
 * code can be exercised without faking the request URL.
 */
final class StubDashboard extends DashboardContext
{
    private function __construct(private readonly bool $current)
    {
    }

    /**
     * Makes service('dashboardContext') answer $current.
     */
    public static function inject(bool $current): void
    {
        Services::injectMock('dashboardContext', new self($current));
    }

    public function isDashboard(): bool
    {
        return $this->current;
    }
}
