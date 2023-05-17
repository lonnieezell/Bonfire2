<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Widgets\Types\Cards;

use Bonfire\Widgets\Interfaces\Item;

/**
 * Represents an individual widget cards.
 *
 * @property string $bgColor
 * @property string $faIcon
 * @property string $title
 * @property string $url
 * @property string $value
 * @property string permission
 */
class CardsItem implements Item
{
    /**
     * @var string|null
     */
    protected $title;

    /**
     * @var string|null
     */
    protected $value;

    /**
     * The permission to check to see if the
     * user can view the widget item or not.
     *
     * @var string
     */
    protected $permission;

    /**
     * FontAwesome 5 icon name
     *
     * @var string|null
     */
    protected $faIcon;

    /**
     * @var string|null
     */
    protected $url;

    /**
     * The assignable background color on the cards widget
     *
     * Possible values are:
     * bg-blue
     * bg-red
     * bg-orange
     * bg-light
     * bg-dark
     * bg-inverse
     * bg-indigo
     * bg-purple
     * bg-pink
     * bg-yellow
     * bg-green
     * bg-teal
     * bg-lime
     * bg-cyan
     * bg-white
     * bg-gray
     * bg-gray-dark
     */
    protected $bgColor;

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

    public function setTitle(?string $title): CardsItem
    {
        $this->title = $title;

        return $this;
    }

    public function setValue(?string $value): CardsItem
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Sets the permission required to see this widget item.
     *
     * @return $this
     */
    public function setPermission(string $permission)
    {
        $this->permission = $permission;

        return $this;
    }

    public function setFaIcon(?string $faIcon): CardsItem
    {
        $this->faIcon = $faIcon;

        return $this;
    }

    public function setUrl(string $url = '#'): CardsItem
    {
        $this->url = strpos($url, '://') !== false
            ? $url
            : '/' . ltrim($url, '/ ');

        return $this;
    }

    /**
     * The assignable background color on the statistics widget
     *
     * Possible values are:
     * bg-blue
     * bg-red
     * bg-orange
     * bg-light
     * bg-dark
     * bg-inverse
     * bg-indigo
     * bg-purple
     * bg-pink
     * bg-yellow
     * bg-green
     * bg-teal
     * bg-lime
     * bg-cyan
     * bg-white
     * bg-gray
     * bg-gray-dark
     */
    public function setBgColor(string $bgColor = 'bg-blue'): CardsItem
    {
        $this->bgColor = $bgColor;

        return $this;
    }

    public function __get(string $key)
    {
        if (method_exists($this, $key)) {
            return $this->{$key}();
        }
    }

    public function title(): ?string
    {
        return strtoupper($this->title);
    }

    public function value(): ?string
    {
        return $this->value;
    }

    public function faIcon(): ?string
    {
        return $this->faIcon;
    }

    public function url(): ?string
    {
        return $this->url;
    }

    public function bgColor(): ?string
    {
        return $this->bgColor;
    }

    /**
     * Can the active user see this widget item?
     */
    public function userCanSee(): bool
    {
        // No permission set means anyone can view.
        if (empty($this->permission)) {
            return true;
        }

        helper('auth');

        return auth()->user()->can($this->permission);
    }
}
