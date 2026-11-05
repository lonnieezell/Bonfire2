<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Content\Config;

use Bonfire\Content\Fields\FieldType;
use CodeIgniter\Config\BaseConfig;

class Content extends BaseConfig
{
    /**
     * The content root: collection configs and entries live under it.
     */
    public string $contentPath = ROOTPATH . 'content/';

    /**
     * Where blueprints live.
     */
    public string $blueprintPath = ROOTPATH . 'resources/blueprints/';

    /**
     * Field types of your own, for blueprints to use: the handle, then a class that extends
     * Bonfire\Content\Fields\FieldType. A handle that a built-in type has is taken over by yours.
     *
     * @var array<string, class-string<FieldType>>
     */
    public array $fieldTypes = [];

    /**
     * Whether Bonfire serves entries through its fallback route.
     * App routes always win over it.
     */
    public bool $autoRoute = false;

    /**
     * Whether Bonfire serves the sitemap at /sitemap.xml.
     * App routes always win over it.
     */
    public bool $sitemap = false;
}
