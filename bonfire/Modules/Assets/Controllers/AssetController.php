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

use CodeIgniter\Controller;

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
        /**
         * De-bust the filename
         *
         * @var string
         */
        $filename     = array_pop($segments);
        $origFilename = $filename;
        $filename     = explode('.', $filename);

        // Must be at least a name and extension
        if (count($filename) < 2) {
            $this->response->setStatusCode(404);

            return;
        }

        // If we have a fingerprint...
        $filename = count($filename) === 3
            ? $filename[0] . '.' . $filename[2]
            : ((count($filename) === 4 && isset($filename[3]))
            ? $filename[0] . '.' . $filename[1] . '.' . $filename[3]
            : $origFilename);

        $folder = config('Assets')->folders[array_shift($segments)];
        $path   = $folder . '/' . implode('/', $segments) . '/' . $filename;

        if (! is_file($path)) {
            $this->response->setStatusCode(404);

            return;
        }

        return $this->response->download($origFilename, file_get_contents($path), true);
    }
}
