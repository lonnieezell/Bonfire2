<?php

namespace Bonfire\Libraries\Widgets;

use Bonfire\Libraries\Widgets\Interfaces\Widgets;

/**
 * Class Manager
 *
 * The main class used to work with widgets in the system.
 *
 * @package Bonfire\Libraries\Widgets\Stats
 */
class Manager
{

	/**
	 * A collection of widgets currently known about.
	 *
	 * @var array
	 */
	public array $widgets = [];

	/**
	 * Creates a new widget in the system.
	 *
	 * @param string $name
	 * @param Widgets $widget
	 *
	 * @return $this
	 */
	public function createWidget($widget, string $name): Manager
	{
		$this->widgets[$name] = new $widget();

		return $this;
	}

	/**
	 * Returns the specified widget instance
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function widget(string $name)
	{
		return $this->widgets[$name];
	}

}