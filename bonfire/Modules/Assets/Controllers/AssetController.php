<?php

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
 * - A CSS file is stored in /themes/admin/css.
 * - The folder is specified as 'admin' => ROOTPATH.'themes/admin'
 * - You can link to the CSS file with 'asset('admin/css/theme.css')'
 */
class AssetController extends Controller
{
    /**
     * Locates and returns the file to the browser
     * with the correct mime-type.
     *
     * @param ...$segments
     */
    public function serve(...$segments)
    {
        // De-bust the filename
        $filename = $origFilename = array_pop($segments);
        $filename = explode('.', $filename);

        // Must be at least a name and extension
        if (count($filename) < 2) {
            $this->response->setStatusCode(404);
            return;
        }

        // If we have a fingerprint...
        $filename = count($filename) === 3
            ? $filename[0] .'.'. $filename[2]
            : $origFilename;

        $folder = config('Assets')->folders[array_shift($segments)];
        $path = $folder .'/'. implode('/',$segments) .'/'. $filename;

        if (! is_file($path)) {
            $this->response->setStatusCode(404);
            return;
        }

        return $this->response->download($origFilename, file_get_contents($path), true);
    }
}