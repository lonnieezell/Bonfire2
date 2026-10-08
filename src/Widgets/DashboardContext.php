<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Widgets;

/**
 * Decides whether a widget item should do the work of fetching its
 * data: only when the dashboard is being shown and the item is enabled.
 *
 * Reached through service('dashboardContext'), so tests can swap it
 * instead of faking the request URL.
 */
class DashboardContext
{
    public function isDashboard(): bool
    {
        return current_url() === config('App')->baseURL . '/' . ADMIN_AREA;
    }

    public function shows(ItemSettings $item): bool
    {
        return $this->isDashboard() && $item->enabled();
    }
}
