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

use Bonfire\Dashboard\LayoutResolver;

/**
 * Decides whether a widget item should do the work of fetching its
 * data: only when the dashboard is being shown, the item is in the
 * viewer's resolved layout, and the viewer holds its permission, if any.
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

    public function shows(ItemSettings $item, ?string $permission = null): bool
    {
        return $this->isDashboard()
            && ($permission === null || auth()->user()?->can($permission))
            && (new LayoutResolver(service('dashboard')))->includes($item->widgetId());
    }
}
