<?php

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Libraries\Menus;

/**
 * Represents an individual item in a menu.
 *
 * @property string $altText
 * @property string $faIcon
 * @property string $icon
 * @property string $iconUrl
 * @property string $title
 * @property string $url
 * @property int    $weight
 */
class MenuItem
{
    /**
     * @var string|null
     */
    protected $title;

    /**
     * @var string|null
     */
    protected $url;

    /**
     * @var string|null
     */
    protected $altText;

    /**
     * FontAwesome 5 icon name
     *
     * @var string|null
     */
    protected $faIcon;

    /**
     * URL to icon, if an image.
     *
     * @var string|null
     */
    protected $iconUrl;

    /**
     * The 'weight' used for sorting.
     *
     * @var int|null
     */
    protected $weight;

    public function __construct(?array $data = null)
    {
        if (! is_array($data)) {
            return;
        }

        foreach ($data as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->{$method}($value);
            }
        }
    }

    /**
     * @return $this
     */
    public function setTitle(string $title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return $this
     */
    public function setUrl(string $url)
    {
        $this->url = strpos($url, '://') !== false
            ? $url
            : '/' . ltrim($url, '/ ');

        return $this;
    }

    /**
     * Sets the URL via reverse routing, so can
     * use named routes to set the URL by.
     *
     * @return $this
     */
    public function setNamedRoute(string $name)
    {
        $this->url = route_to($name);

        return $this;
    }

    /**
     * @return $this
     */
    public function setAltText(string $text)
    {
        $this->altText = $text;

        return $this;
    }

    /**
     * Sets the FontAwesome icon name, like:
     *
     * - fa-pencil
     * - fal fa-alarm-clock
     *
     * @return $this
     */
    public function setFontAwesomeIcon(string $icon)
    {
        $this->faIcon = $icon;

        return $this;
    }

    /**
     * Sets the URL to the icon, if it's an image.
     *
     * @return $this
     */
    public function setIconUrl(string $url)
    {
        $this->iconUrl = $url;

        return $this;
    }

    /**
     * Sets the "weight" of the menu item.
     * The large the value, the later in the menu
     * it will appear.
     *
     * @return $this
     */
    public function setWeight(int $weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * @return string
     */
    public function title()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function url()
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function altText()
    {
        return $this->altText;
    }

    /**
     * Returns the full icon tag: either a <i> tag for FontAwesome
     * icons, or an <img> tag for images.
     */
    public function icon(string $class = ''): string
    {
        if (! empty($this->faIcon)) {
            return $this->buildFontAwesomeIconTag($class);
        }
        if (! empty($this->iconUrl)) {
            return $this->buildImageIconTag($class);
        }

        return '';
    }

    /**
     * @return int
     */
    public function weight()
    {
        return $this->weight ?? 0;
    }

    /**
     * Returns the full FontAwesome tag.
     */
    protected function buildFontAwesomeIconTag(string $class): string
    {
        $class = ! empty($class)
            ? " {$class}"
            : '';

        return "<i class=\"{$this->faIcon}{$class}\"></i>";
    }

    /**
     * Returns a full img tag for our icon.
     *
     * @return string
     */
    protected function buildImageIconTag(string $class)
    {
        $class = ! empty($class)
            ? "class=\"{$class}\" "
            : '';

        $iconUrl = strpos($this->iconUrl, '://') !== false
            ? $this->iconUrl
            : '/' . ltrim($this->iconUrl, '/ ');

        return "<img href=\"{$iconUrl}\" alt=\"{$this->title}\" {$class}/>";
    }

    public function __get(string $key)
    {
        if (method_exists($this, $key)) {
            return $this->{$key}();
        }
    }
}
