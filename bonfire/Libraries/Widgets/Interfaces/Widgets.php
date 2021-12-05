<?php

namespace Bonfire\Libraries\Widgets\Interfaces;

interface Widgets
{

	/**
	 * Adds a new item
	 *
	 * @param Item $item
	 *
	 * @return self
	 */
	public function addItem(Item $item): self;

}