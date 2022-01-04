<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Libraries\Menus;

trait HasMenuIcons
{
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
}
