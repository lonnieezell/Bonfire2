<?php

namespace Bonfire\Dashboard;

class DashboardCells
{
    /**
     * Displays a selection of "Quick Links" in the admin dashboard.
     * This is a view cell that uses the "content" sidebar menu
     * items to determine the links to show.
     */
    public function quickLinks()
    {
        return view('Bonfire\Dashboard\Views\quick_links', [
            'menu' => service('menus')->menu('sidebar')->collection('content'),
        ]);
    }
}
