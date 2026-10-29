<?php

namespace Bonfire\Config;

class Themes
{
    /**
     * ---------------------------------------------------------------
     * THEME COLLECTIONS
     * ---------------------------------------------------------------
     *
     * Contains the "root" folders that hold our themes. Each theme
     * must be a direct child of the root folder.
     *
     * For example:
     *  $themeCollection = [ROOTPATH.'themes']
     *  /themes         - the "collection" folder
     *      /admin      - a theme
     *      /app        - a theme
     *
     * @var array
     */
    public $collections = [
        __DIR__ . '/../../themes/',
    ];

    /**
     * --------------------------------------------------------------------------
     * Components' default lookup paths
     * --------------------------------------------------------------------------
     *
     * The list of paths to look for a component as a fallback (by order of priority).
     * Useful when having a components library to be used across multiple themes.
     *
     * NB: paths MUST end with a slash (/)
     */
    public $componentsLookupPaths = [
        APPPATH . 'Views/Components/',
    ];
}
