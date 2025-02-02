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
            throw PageNotFoundException::forPageNotFound();
        }
        $ext = $fileparts[$count - 1];

        // To keep backward compatibility, we will assume there might not be
        // a separator defined in user's config
        $separator = config('Assets')->separator ?? '~~';
        $parts     = explode($separator, $filename);
        $filename = count($parts) === 2 ? $parts[0] . '.' . $ext : $origFilename;
        $baseAssetFolders = config('Assets')->folders; // get list of folders with assets
        $targetBaseAssetFolder = array_shift($segments); // from segments choose the first one as main folder
        $folder = $baseAssetFolders[$targetBaseAssetFolder] ?? ROOTPATH . '/somer^3andomWhatever'; // point to folder in the website or a non-existent folder within root path

        $path = $folder . '/' . implode('/', $segments) . '/' . $filename;
        if (! is_file($path) || empty($folder)) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->response->download($origFilename, file_get_contents($path), true)->inline();
    }
}
