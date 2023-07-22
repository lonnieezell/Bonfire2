<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Resources;

/**
 * Represents a single tab that should be displayed for a resource.
 * A resource is a User, a User Group, etc.
 *
 * @property string $permission
 * @property string $title
 * @property string $url
 */
class ResourceTab
{
    /**
     * The title displayed to the user.
     *
     * @var string
     */
    protected $title;

    /**
     * The URL used to link the tab to.
     *
     * @var string
     */
    protected $url;

    /**
     * The permission needed to view the tab
     *
     * @var string
     */
    protected $permission;

    /**
     * Constructor.
     *
     * Use $params to fill the values on creation.
     */
    public function __construct(?array $params = null)
    {
        if (is_array($params)) {
            foreach ($params as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->{$key} = $value;
                }
            }
        }
    }

    /**
     * Ensures the link is a site link, within the
     * admin area, that handles id replacement for
     * the current resource.
     */
    public function getUrl(): string
    {
        if (empty($this->url)) {
            return '';
        }

        $url = $this->url;

        if (strpos($this->url, '(id)') !== false) {
            $url = $this->fillPlaceholder($url);
        }

        $url = ADMIN_AREA . '/' . $url;

        return site_url($url);
    }

    /**
     * Attempts to fill the placeholder with the
     * resource ID from the current URL.
     *
     * @return string
     */
    private function fillPlaceholder(string $url)
    {
        // Get the portion of path up to the id
        $path = parse_url($url, PHP_URL_PATH);
        $path = trim(substr($path, 0, strpos($path, '(id)')), ' /');

        // Parse out the current resource ID - $matches[1] should contain it
        preg_match("|{$path}\\/([0-9]+)|i", current_url(), $matches);

        if (isset($matches[1]) && ! empty($matches[1])) {
            return str_replace('(id)', $matches[1], $url);
        }

        return $url;
    }

    /**
     * Magic getter
     */
    public function __get(string $key)
    {
        if ($key === 'url') {
            return $this->getUrl();
        }

        if (property_exists($this, $key)) {
            return $this->{$key};
        }
    }

    /**
     * Magic setting
     *
     * @param mixed $value
     */
    public function __set(string $key, $value)
    {
        if (property_exists($this, $key)) {
            $this->{$key} = $value;
        }
    }
}
