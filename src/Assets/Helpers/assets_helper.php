<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */
if (! defined('asset_link')) {
    /**
     * Generates the URL to serve an asset to the client
     *
     * @param string $type css, js
     */
    function asset_link(string $location, string $type): string
    {
        $url = asset($location, $type);

        $tag = '';

        switch ($type) {
            case 'css':
                $tag = "<link href='{$url}' rel='stylesheet' />";
                break;

            case 'js':
                $tag = "<script src='{$url}'></script>";
        }

        return $tag;
    }
}

if (! defined('asset')) {
    function asset(string $location, string $type): string
    {
        $config   = config('Assets');
        $location = trim($location, ' /');

        // Add a cache-busting fingerprint to the filename
        $segments   = explode('/', $location);
        $filename   = array_pop($segments);
        $ext        = substr($filename, strrpos($filename, '.'));
        $namelength = strlen($filename) - strlen($ext);
        $name       = substr($filename, 0, $namelength);

        if (empty($filename) || empty($ext) || $filename === $ext || empty($segments)) {
            throw new \RuntimeException('You must provide a valid filename and extension to the asset() helper.');
        }

        // VERSION cache-busting
        $fingerprint = '';
        $separator   = $config->separator ?? '~~';
        if ($config->bustingType === 'version') {
            switch (ENVIRONMENT) {
                case 'testing':
                case 'development':
                    $fingerprint = $separator . time();
                    break;

                default:
                    $fingerprint = $separator . $config->versions[$type];
            }
        }
        // FILE cache-busting
        if ($config->bustingType === 'file') {
            $tempSegments = $segments;
            array_shift($tempSegments);
            $path = rtrim($config->folders[current($segments)], ' /') . '/' . implode(
                '/',
                $tempSegments
            ) . '/' . $filename;

            $filetime = filemtime($path);
            
            if (!$filetime) {
                throw new \RuntimeException('Unable to get modification time of asset file: ' . $filename);
            }
            $fingerprint = $separator . $filetime;
        }

        $filename = $name . $fingerprint . $ext;

        // Stitch the location back together
        $segments[] = $filename;
        $location   = implode('/', $segments);
        $url        = "/assets/{$location}";

        return base_url($url);
    }
}
