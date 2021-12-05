<?php

namespace Bonfire\Libraries\Widgets\Stats;

use Bonfire\Libraries\Widgets\Interfaces\Item;

/**
 * Represents an individual widget stats.
 *
 * @package Bonfire\Libraries\Widgets\Stats
 *
 * @property string $title
 * @property string $value
 * @property string $faIcon
 * @property string $url
 * @property string $bgColor
 */
class StatsItem implements Item
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
	 * @var string|null
	 */
	protected $bgColor;

	public function __construct(array $data=null)
	{
		if (! is_array($data)) {
			return;
		}
		foreach ($data as $key => $value) {
			$method = 'set'.ucfirst($key);
			if (method_exists($this, $method)) {
				$this->{$method}($value);
			}
		}
	}

	/**
	 * @param string|null $title
	 */
	public function setTitle(?string $title): StatsItem
	{
		$this->title = $title;

		return $this;
	}

	/**
	 * @param string|null $value
	 */
	public function setValue(?string $value): StatsItem
	{
		$this->value = $value;

		return $this;
	}

	/**
	 * @param string|null $faIcon
	 */
	public function setFaIcon(?string $faIcon): StatsItem
	{
		$this->faIcon = $faIcon;

		return $this;
	}

	/**
	 * @param string $url
	 */
	public function setUrl(string $url): StatsItem
	{
		$this->url = strpos($url, '://') !== false
			? $url
			: '/' . ltrim($url, '/ ') ;

		return $this;
	}

	/**
	 * @param string|null $bgColor
	 */
	public function setBgColor(?string $bgColor): StatsItem
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

	/**
	 * @return string|null
	 */
	public function title(): ?string
	{
		return strtoupper($this->title);
	}

	/**
	 * @return string|null
	 */
	public function value(): ?string
	{
		return $this->value;
	}

	/**
	 * @return string|null
	 */
	public function faIcon(): ?string
	{
		return $this->faIcon;
	}

	/**
	 * @return string|null
	 */
	public function url(): ?string
	{
		return $this->url;
	}

	/**
	 * @return string|null
	 */
	public function bgColor(): ?string
	{
		return $this->bgColor;
	}

}