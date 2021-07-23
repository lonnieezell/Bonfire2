<?php namespace Bonfire\Libraries\Menus;

/**
 * Represents an individual item in a menu.
 *
 * @package Bonfire\Menus
 */
class MenuItem
{
	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var string
	 */
	protected $url;

	/**
	 * @var string
	 */
	protected $altText;

	/**
	 * FontAwesome 5 icon name
	 *
	 * @var string
	 */
	protected $faIcon;

	/**
	 * URL to icon, if an image.
	 *
	 * @var string
	 */
	protected $iconUrl;

	/**
	 * The 'weight' used for sorting.
	 *
	 * @var int
	 */
	protected $weight;

    public function __construct(array $data=null)
    {
        if (! is_array($data)) {
            return;
        }

        foreach($data as $key => $value) {
            $method = 'set'.ucfirst($key);
            if (method_exists($this, $method)) {
                $this->{$method}($value);
            }
        }
	}

	/**
	 * @param string $title
	 *
	 * @return $this
	 */
	public function setTitle(string $title)
	{
		$this->title = $title;

		return $this;
	}

	/**
	 * @param string $url
	 *
	 * @return $this
	 */
	public function setUrl(string $url)
	{
		$this->url = strpos($url, '://') !== false
			? $url
			: '/'. ltrim($url, '/ ');

		return $this;
	}

	/**
	 * Sets the URL via reverse routing, so can
	 * use named routes to set the URL by.
	 *
	 * @param string $name
	 *
	 * @return $this
	 */
	public function setNamedRoute(string $name)
	{
		$this->url = route_to($name);

		return $this;
	}

	/**
	 * @param string $text
	 *
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
	 * @param string $icon
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
	 * @param string $url
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
	 * @param int $weight
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
	 *
	 * @param string $class
	 *
	 * @return string
	 */
	public function icon(string $class = ''): string
	{
		if (! empty($this->faIcon))
		{
			return $this->buildFontAwesomeIconTag($class);
		}
		elseif (! empty($this->iconUrl))
		{
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
	 *
	 * @param string $class
	 *
	 * @return string
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
	 * @param string $class
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
			: '/'. ltrim($this->iconUrl, '/ ');

		return "<img href=\"{$iconUrl}\" alt=\"{$this->title}\" {$class}/>";
	}

    public function __get(string $key)
    {
        if (method_exists($this, $key)) {
            return $this->{$key}();
        }
	}
}
