<?php

if (! defined('asset_link')) {
    /**
     * Generates the URL to serve an asset to the client
     *
     * @param string $location
     * @param string $type css, js
     *
     * @return string
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
    /**
     * @param string $location
     *
     * @return string
     */
    function asset(string $location, string $type): string
    {
        $config = config('Assets');
        $location = trim($location, ' /');

        // Add a cache-busting fingerprint to the filename
        $segments = explode('/', $location);
        $filename = array_pop($segments);
        $ext = substr($filename, strrpos($filename, '.'));

        if (empty($filename) || empty($ext) || $filename === $ext || empty($segments)) {
            throw new \RuntimeException('You must provide a valid filename and extension to the asset() helper.');
        }

        // VERSION cache-busting
        $fingerprint = '';
        if ($config->bustingType === 'version') {
            switch (ENVIRONMENT) {
                case 'testing':
                case 'development':
                    $fingerprint = time();
                    break;
                default:
                    $fingerprint = $config->versions[$type];
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

            $fingerprint = filemtime($path);

            if ($fingerprint === false) {
                throw new \RuntimeException('Unable to get modification time of asset file: ' . $filename);
            }
        }

        $filename = str_replace($ext, '.' . $fingerprint . $ext, $filename);

        // Stitch the location back together
        array_push($segments, $filename);
        $location = implode('/', $segments);
        $url = "/assets/{$location}";

        return base_url($url);
    }
}
