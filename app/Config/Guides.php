<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Settings related to the Guides (docs)
 * available on this site.
 */
class Guides extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Available Collections
     * --------------------------------------------------------------------------
     *
     * Defines the list of available guide collections. Each collection is given
     * an "alias", which is the array key. This is how the collection will be
     * referenced everywhere else in code.
     *
     * Each definition includes the following information:
     *      - title: The name of the collection when displayed to the user
     *      - path: The location of the main folder, relative to project root
     *      - permission: The permission that is checked against to see if the
     *          user can view this guide.
     *
     * @var array
     */
    public $collections = [
        'bonfire' => [
            'title'      => 'Bonfire Developer Guides',
            'path'       => '_docs',
            'permission' => 'guides.bonfire',
        ],
        'app' => [
            'title'      => 'Application Guides',
            'path'       => 'app/_guides',
            'permission' => 'guides.application',
        ],
    ];
}
