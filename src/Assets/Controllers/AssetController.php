<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Assets\Controllers;

use Bonfire\Assets\AssetLocator;
use CodeIgniter\Controller;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Responsible for serving css/js/image assets from
 * non-web-accessible folders as if they were in the
 * /assets folder.
 *
 * Folders to search are defined in Config\Assets.
 * The folder name becomes the place where the assets
 * is searched for.
 *
 * Example:
 * - A CSS file is stored in /themes/Admin/css.
 * - The folder is specified as 'admin' => ROOTPATH.'themes/Admin'
 * - You can link to the CSS file with 'asset('admin/css/theme.css')'
 */
class AssetController extends Controller
{
    /**
     * Locates and returns the file to the browser
     * with the correct mime-type.
     *
     * @param string ...$segments
     */
    public function serve(...$segments)
    {
        $path = (new AssetLocator())->locate($segments);

        if ($path === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->response->download(end($segments), file_get_contents($path), true)->inline();
    }
}
