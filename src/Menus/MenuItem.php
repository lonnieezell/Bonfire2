<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * php version 8.0
 *
 * @category Menus
 * @license  MIT https://opensource.org/licenses/MIT
 * @see      https://github.com/lonnieezell/Bonfire2/
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
     * Title of Menu Item
     */
    protected ?string $title;

    /**
     * URL of Menu Item
     */
    protected ?string $url;

    /**
     * Alternative Text of Menu Item
     */
    protected ?string $altText;

    /**
     * The 'weight' used for sorting.
     */
    protected ?int $weight;

    /**
     * The permission to check to see if the user can view the menu item or not.
     */
    protected string $permission;

    /**
     * Route for use in setting weight based on values from config
     */
    private string $_namedRoute = '';

    /**
     * Undocumented function
     *
     * @param array|null $data Array of Menu Item's data
     */
    public function __construct(?array $data = null)
    {
        if (! is_array($data)) {
            return;
        }

        // store the named route to use to draw  the weight of the
        // item from settings
        if (isset($data['namedRoute']) && is_string($data['namedRoute'])) {
            $this->_namedRoute = $data['namedRoute'];
        }

        if (! isset($data['weight'])) {
            $data['weight'] = 0;
        }

        foreach ($data as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->{$method}($value);
            }
        }
    }

    /**
     * Sets Title of this Menu Item
     *
     * @param string $title Title of Menu Item
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Sets URL of this Menu Item
     *
     * @param string $url Url of Menu Item
     */
    public function setUrl(string $url): self
    {
        $this->url = strpos($url, '://') !== false ? $url : '/' . ltrim($url, '/ ');

        return $this;
    }

    /**
     * Sets the URL via reverse routing, so can use named routes to set the URL by.
     *
     * @param string $name NamedRoute or alias of
     */
    public function setNamedRoute(string $name): self
    {
        $this->url = url_to($name);

        return $this;
    }

    /**
     * Sets the Alternative Text of this Menu Item
     *
     * @param string $text AltText for Menu Item
     */
    public function setAltText(string $text): self
    {
        $this->altText = $text;

        return $this;
    }

    /**
     * Sets the "weight" of the menu item.
     * The large the value, the later in the menu it will appear.
     * Uses the value from Config/Bonfire.php $menuWeights if it is set, key is
     * the unique named route.
     *
     * @param int $weight Weight of Menu Item
     */
    public function setWeight(int $weight): self
    {
        $defaultMenuWeight = setting('Bonfire.menuWeights');

        if ($this->_namedRoute && isset($defaultMenuWeight[$this->_namedRoute])) {
            $this->weight = setting('Bonfire.menuWeights')[$this->_namedRoute];
        } else {
            $this->weight = $weight;
        }

        return $this;
    }

    /**
     * Sets the permission required to see this menu item.
     *
     * @param string $permission Permission for user can
     */
    public function setPermission(string $permission): self
    {
        $this->permission = $permission;

        return $this;
    }

    /**
     * Gets Title of this Menu Item
     */
    public function title(): string
    {
        return $this->title;
    }

    /**
     * Gets Url of this Menu Item
     */
    public function url(): string
    {
        return $this->url;
    }

    /**
     * Gets altText of this Menu Item
     */
    public function altText(): string
    {
        return $this->altText;
    }

    /**
     * Gets Weight of this Menu Item
     */
    public function weight(): int
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

    /**
     * Gets callable of MenuItem Class
     *
     * @param string $key one of the Method name of MenuItem Class
     *
     * @return mixed
     */
    public function __get(string $key)
    {
        if (method_exists($this, $key)) {
            return $this->{$key}();
        }
    }
}
