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

namespace Bonfire\Assets;

use Bonfire\Assets\Config\Assets;
use InvalidArgumentException;
use RuntimeException;

/**
 * Owns the asset URL format:
 *
 *     /assets/<folder>/<dirs>/<name><separator><fingerprint>.<ext>
 *
 * `url()` builds it for `asset()`, `locate()` reads it back for
 * `AssetController`, so the two can't drift apart.
 */
final readonly class AssetLocator
{
    private Assets $config;

    public function __construct()
    {
        $this->config = config('Assets');
    }

    /**
     * The URL that serves the asset, with its cache-busting fingerprint.
     *
     * @param string $location Relative URL to the asset, starting with a folder name from Config\Assets
     * @param string $type     'css' or 'js', which picks the version used by the 'version' busting type
     */
    public function url(string $location, string $type): string
    {
        $location     = trim($location, ' /');
        $relativePath = parse_url($location, PHP_URL_PATH);

        if (str_contains($location, '://') || $relativePath === false || $relativePath === '') {
            throw new InvalidArgumentException('$location must be a relative URL to the file.');
        }

        $segments = explode('/', $location);
        $filename = array_pop($segments);
        $pathinfo = pathinfo($filename);
        $ext      = $pathinfo['extension'] ?? '';
        $name     = $pathinfo['filename'];

        if ($filename === '' || $name === '' || $ext === '') {
            throw new RuntimeException('You must provide a valid filename and extension to the asset() helper.');
        }

        $filename   = $name . $this->fingerprint($this->path($segments, $filename), $type, $location) . '.' . $ext;
        $segments[] = $filename;

        return base_url('/assets/' . implode('/', $segments));
    }

    /**
     * The file that the segments of an `/assets/...` URL point to,
     * or null when there is no such asset.
     *
     * @param list<string> $segments The URL segments after `/assets/`
     */
    public function locate(array $segments): ?string
    {
        foreach ($segments as $segment) {
            // Never let a URL climb out of a configured folder.
            if ($segment === '..' || str_contains($segment, "\0") || str_contains($segment, '\\')) {
                return null;
            }
        }

        $requested = array_pop($segments);
        $fileparts = explode('.', (string) $requested);
        $count     = count($fileparts);

        // Must be at least a name and extension
        if ($requested === null || $count < 2) {
            return null;
        }

        // De-bust the filename
        $parts    = explode($this->separator(), $requested);
        $filename = count($parts) === 2 ? $parts[0] . '.' . $fileparts[$count - 1] : $requested;
        $path     = $this->path($segments, $filename);

        return $path !== null && is_file($path) ? $path : null;
    }

    /**
     * To keep backward compatibility, we assume there might not be
     * a separator defined in user's config.
     */
    private function separator(): string
    {
        return $this->config->separator ?? '~~';
    }

    /**
     * Where the asset lives on disk, or null when the first segment
     * is not a configured folder.
     *
     * @param list<string> $segments Folder name, then any sub-directories
     */
    private function path(array $segments, string $filename): ?string
    {
        $folder = array_shift($segments);
        $base   = $folder === null ? null : ($this->config->folders[$folder] ?? null);

        if (empty($base)) {
            return null;
        }

        return rtrim($base, ' /') . '/' . implode('/', [...$segments, $filename]);
    }

    private function fingerprint(?string $path, string $type, string $location): string
    {
        $separator = $this->separator();

        if ($path === null || ! file_exists($path)) { // Possible case of missing asset
            log_message('warning', 'Missing asset: ' . ($path ?? $location));

            return $separator . 'asset-is-missing';
        }

        if ($this->config->bustingType === 'version') { // Asset version-based cache-busting
            return match (ENVIRONMENT) {
                'testing', 'development' => $separator . time(),
                default                  => $separator . $this->config->versions[$type],
            };
        }

        if ($this->config->bustingType === 'file') { // Mod time-based cache-busting
            $filetime = filemtime($path);
            if (! $filetime) {
                throw new RuntimeException('Unable to get modification time of asset file: ' . basename($path));
            }

            return $separator . $filetime;
        }

        return '';
    }
}
