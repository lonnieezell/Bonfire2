<?php

namespace Bonfire\Config;

use CodeIgniter\Config\BaseConfig;

class Site extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Items Per Page
     * --------------------------------------------------------------------------
     *
     * The number of items that should be displayed in content lists.
     */
    public $perPage = 15;

    /**
     * --------------------------------------------------------------------------
     * Base Site URL
     * --------------------------------------------------------------------------
     *
     * The name that should be displayed for the site.
     */
    public $siteName = 'Bonfire';

    /**
     * --------------------------------------------------------------------------
     * Site Online?
     * --------------------------------------------------------------------------
     *
     * When false, only superadmins and user groups with permission will be
     * able to view the site. All others will see the "System Offline" page.
     */
    public $siteOnline = true;

    /**
     * --------------------------------------------------------------------------
     * Site Offline View
     * --------------------------------------------------------------------------
     *
     * The view file that is displayed when the site is offline.
     */
    public $siteOfflineView = 'Bonfire\Views\site_offline';
}
