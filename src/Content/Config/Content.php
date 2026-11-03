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
     * Whether Bonfire serves entries through its fallback route.
     * App routes always win over it.
     */
    public bool $autoRoute = false;
}
