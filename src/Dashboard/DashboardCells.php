<?php

namespace Bonfire\Dashboard;

use Bonfire\Dashboard\Widgets\QuickLinksWidget;

class DashboardCells
{
    /**
     * Displays a selection of "Quick Links" in the admin dashboard.
     *
     * @deprecated Quick Links is now QuickLinksWidget, registered by the Dashboard module.
     */
    public function quickLinks()
    {
        return (new QuickLinksWidget())->render();
    }
}
