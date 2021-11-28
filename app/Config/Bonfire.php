<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Bonfire extends BaseConfig
{
    public $views = [
        'filter_list' => 'Bonfire\Views\_filter_list',
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
        APPPATH . 'Views/Components/'
    ];
}
