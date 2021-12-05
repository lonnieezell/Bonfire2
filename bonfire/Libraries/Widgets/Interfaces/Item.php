<?php

namespace Bonfire\Libraries\Widgets\Interfaces;

interface Item
{
	/**
	 * @param string|null $title
	 */
	public function setTitle(?string $title): Item;

}