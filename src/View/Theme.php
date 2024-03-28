<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\View;

/**
 * Class Theme
 *
 * Provides utility commands to work with themes.
 */
class Theme
{
    /**
     * @var string
     */
    protected static $defaultTheme = 'app';

    /**
     * @var string
     */
    protected static $currentTheme;

    /**
     * Holds theme info retrieved
     *
     * @var array
     */
    protected static $themeInfo;

    /**
     * Sets the active theme.
     */
    public static function setTheme(string $theme)
    {
        static::$currentTheme = $theme;
    }

    /**
     * Returns the path to the specified theme folder.
     * If no theme is provided, will use the current theme.
     */
    public static function path(?string $theme = null): string
    {
        if (empty($theme)) {
            $theme = static::current();
        }

        // Ensure we've pulled the theme info
        if (empty(static::$themeInfo)) {
            static::$themeInfo = self::available();
        }

        foreach (static::$themeInfo as $info) {
            if ($info['name'] === $theme) {
                return $info['path'];
            }
        }

        return '';
    }

    /**
     * Returns the name of the active theme.
     *
     * @return string
     */
    public static function current()
    {
        return empty(static::$currentTheme)
            ? static::$defaultTheme
            : static::$currentTheme;
    }

    /**
     * Returns an array of all available themes
     * and the paths to their directories.
     */
    public static function available(): array
    {
        $themes = [];
        helper('filesystem');

        foreach (config('Themes')->collections as $collection) {
            $info = get_dir_file_info($collection, true);

            if ($info === []) {
                continue;
            }

            foreach ($info as $name => $row) {
                $themes[] = [
                    'name'          => $name,
                    'path'          => $row['relative_path'] . $name . '/',
                    'hasComponents' => is_dir($row['relative_path'] . $name . '/Components'),
                ];
            }
        }

        return $themes;
    }
}
