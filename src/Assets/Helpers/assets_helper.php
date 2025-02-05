<?php

declare(strict_types=1);

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
     * Generates the HTML tag with URL to serve an asset to the client
     *
     * @param string $location   Relative URL to asset file
     * @param string $type       css, js
     * @param mixed  $attributes Additional attributes to include in the asset link tag.
     *                           Can be provided as a string (for value-less attributes like "defer")
     *                           or an associative array of attribute-value pairs.
     *                           Defaults to null.
     */
    function asset_link(string $location, string $type, mixed $attributes = null): string
    {
        $tag = '';
        $url = asset($location, $type);

        $additionalAttr = '';
        $defaultAttr    = $type === 'css' ? "rel='stylesheet'" : '';

        if (is_string($attributes)) {
            $additionalAttr = $attributes;
        }

        if (is_array($attributes)) {
            foreach ($attributes as $key => $value) {
                // if the array already includes the 'rel', remove the default
                if ($key === 'rel') {
                    $defaultAttr = '';
                }

                $additionalAttr .= "{$key}='{$value}' ";
            }
        }

        $additionalAttr .= $defaultAttr;

        switch ($type) {
            case 'css':
                $tag = "<link href='{$url}' {$additionalAttr} />";
                break;

            case 'js':
                $tag = "<script src='{$url}' {$additionalAttr}></script>";
        }

        return $tag;
    }
}

if (!defined('asset')) {
    /**
     * Generates the URL to serve an asset to the client
     *
     * @param string $location Relative URL to asset file
     * @param string $type     'file|version' string
     */
    function asset(string $location, string $type): string
    {
        $location     = trim($location, ' /');
        $relativePath = parse_url($location, PHP_URL_PATH);

        if (str_contains($location, '://') || $relativePath === false || $relativePath === '') {
            throw new InvalidArgumentException('$location must be a relative URL to the file.');
        }

        // Add a cache-busting fingerprint to the filename
        $config   = config('Assets');
        $segments = explode('/', ltrim($location, '/'));
        $filename = array_pop($segments);
        $pathinfo = pathinfo($filename);
        $ext      = $pathinfo['extension'] ?? '';
        $name     = $pathinfo['filename'];

        if ($filename === '' || $name === '' || $ext === '') {
            throw new RuntimeException('You must provide a valid filename and extension to the asset() helper.');
        }

        $fingerprint  = '';
        $separator    = $config->separator ?? '~~';
        $tempSegments = $segments;
        array_shift($tempSegments);
        $path = rtrim($config->folders[current($segments)], ' /') . '/' . implode(
            '/',
            $tempSegments
        ) . '/' . $filename;

        if (! file_exists($path)) { // Possible case of missing asset

            $fingerprint = $separator . 'asset-is-missing';

        } elseif ($config->bustingType === 'version') { // Asset version-based cache-busting

            switch (ENVIRONMENT) {
                case 'testing':
                case 'development':
                    $fingerprint = $separator . time();
                    break;

                default:
                    $fingerprint = $separator . $config->versions[$type];
            }

        } elseif ($config->bustingType === 'file') { // Mod time-based cache-busting

            $filetime = filemtime($path);
            if (!$filetime) {
                throw new RuntimeException('Unable to get modification time of asset file: ' . $filename);
            }
            $fingerprint = $separator . $filetime;
        }

        $filename = $name . $fingerprint . '.' . $ext;

        // Stitch the location back together
        $segments[] = $filename;
        $location   = implode('/', $segments);
        $url        = "/assets/{$location}";

        return base_url($url);
    }
}
