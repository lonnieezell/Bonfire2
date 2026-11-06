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

    /**
     * Whether Bonfire puts an entry's SEO tags in the head of the pages it renders through a collection's template.
     * A collection that hands off to a controller is never touched: call content_seo() there.
     */
    public bool $autoSeo = false;

    /**
     * Whether Bonfire serves the sitemap at /sitemap.xml.
     * App routes always win over it.
     */
    public bool $sitemap = false;

    /**
     * Whether Bonfire serves a collection's feed at /feed/{collection}.
     * App routes always win over it.
     */
    public bool $feedRoute = false;
}
