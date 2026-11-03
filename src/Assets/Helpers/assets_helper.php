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

use Bonfire\Assets\AssetLocator;

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
        $url = esc(asset($location, $type));

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

                $additionalAttr .= esc((string) $key) . "='" . esc((string) $value) . "' ";
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

if (! defined('asset')) {
    /**
     * Generates the URL to serve an asset to the client
     *
     * @param string $location Relative URL to asset file
     * @param string $type     css, js
     */
    function asset(string $location, string $type): string
    {
        return (new AssetLocator())->url($location, $type);
    }
}
