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
        $fileparts    = explode('.', $filename);
        $count        = count($fileparts);
        // Must be at least a name and extension
        if ($count < 2) {
            $this->response->setStatusCode(404);

            return;
        }
        $ext = $fileparts[$count - 1];

        // To keep backward compatibility, we will assume there might not be
        // a separator defined in user's config
        $separator = config('Assets')->separator ?? '~~';
        $parts     = explode($separator, $filename);
        if (count($parts) === 2) {
            $filename = $parts[0] . '.' . $ext;
        } else {
            $filename = $origFilename;
        }

        $folder = config('Assets')->folders[array_shift($segments)];

        $path = $folder . '/' . implode('/', $segments) . '/' . $filename;
        if (! is_file($path)) {
            $this->response->setStatusCode(404);

            return;
        }

        return $this->response->download($origFilename, file_get_contents($path), true);
    }
}
