<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Assets\Config;

use CodeIgniter\Config\BaseConfig;

class Assets extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Cache Busting Type
     * --------------------------------------------------------------------------
     *
     * Assets can work with 2 different methods to determine the cache busting
     * string, either 'file' or 'version'.
     *
     * 'version' uses the Cache Busting Version setting below on production servers,
     * but uses the current timestamp for testing/development environments. This
     * has lower server load, but does require the version number to be updated
     * after every change.
     *
     * 'file' uses the modified time on the file. This ensures it is always up to
     * date and no chance for a developer to forget to update it. However, this
     * does increase server load as we're reading the value of the file on every
     * non-cached page load. This should probably only be used on small-medium
     * sites.
     */
    public $bustingType = 'file';

    /**
     * --------------------------------------------------------------------------
     * Separator for the cache busting part of the file
     * --------------------------------------------------------------------------
     *
     * It can be a single web-safe non-reserved character or a combination of 
     * such characters (characters that are allowed in a URI, but do not have a 
     * reserved purpose) that DO NOT OCCUR in your asset file names 
     * (like `~`, `-` or `_` or any combination of ASCII letters and numbers).
     * Separator will be inserted before the file version/timestamp, in between 
     * the file name and file extension.
     */
    public $separator = '~~';

    /**
     * --------------------------------------------------------------------------
     * Cache Busting Versions
     * --------------------------------------------------------------------------
     *
     * The version of the css that gets shipped to production. When changes
     * happen, this should be updated to reflect the changes. We do it this
     * way instead of relying on server file modification time to reduce
     * server load.
     */
    public $versions = [
        'css' => '1.0',
        'js'  => '1.0',
    ];

    /**
     * --------------------------------------------------------------------------
     * Asset Locations
     * --------------------------------------------------------------------------
     *
     * Specifies the locations that can have assets located in them.
     * This should make up the first segment of an asset URL.
     */
    public $folders = [
        'app'   => __DIR__ . '/../../../themes/App',
        'admin' => __DIR__ . '/../../../themes/Admin',
        'auth'  => __DIR__ . '/../../../themes/Auth',
        'other' => VENDORPATH,
    ];
}
