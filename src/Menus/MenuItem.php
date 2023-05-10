<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Menus;

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
    use HasMenuIcons;

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
     * The 'weight' used for sorting.
     *
     * @var int|null
     */
    protected $weight;

    /**
     * The permission to check to see if the
     * user can view the menu item or not.
     *
     * @var string
     */
    protected $permission;

    /**
     * Route for use in setting weight
     * based on values from config
     * 
     * @var string
     */
    private $namedRoute;

    public function __construct(?array $data = null)
    {
        if (!is_array($data)) {
            return;
        }

        // store the named route to use to draw  the weight of the
        // item from settings
        if (
            isset($data['namedRoute'])
            && is_string($data['namedRoute'])
        ) {
            $this->namedRoute = $data['namedRoute'];
        }

        if (! isset($data['weight']))
            $data['weight'] = 0;

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
        $this->url = url_to($name);

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
     * Sets the "weight" of the menu item.
     * The large the value, the later in the menu
     * it will appear.
     * Uses the value from Config/Bonfire.php $menuWeights
     * if it is set, key is the unique named route.
     *
     * @return $this
     */
    public function setWeight(int $weight)
    {
        if (
            $this->namedRoute
            && isset(setting('Bonfire.menuWeights')[$this->namedRoute])
        )
            $this->weight = setting('Bonfire.menuWeights')[$this->namedRoute];
        else
            $this->weight = $weight;

        return $this;
    }

    /**
     * Sets the permission required to see this menu item.
     *
     * @return $this
     */
    public function setPermission(string $permission)
    {
        $this->permission = $permission;

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
     * @return int
     */
    public function weight()
    {
        return $this->weight ?? 0;
    }

    /**
     * Can the active user see this menu item?
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

    public function __get(string $key)
    {
        if (method_exists($this, $key)) {
            return $this->{$key}();
        }
    }
}
