<?php

namespace Bonfire\Libraries\Widgets\Stats;

/**
 * Represents a collection of stats items.
 *
 * @package Bonfire\Libraries\Widgets\Stats
 *
 * @property string $name
 * @property string $title
 */
class StatsCollection extends StatsItem
{
	/**
	 * @var array
	 */
	protected $items = [];

	/**
	 * The name this collection is discovered by.
	 *
	 * @var string
	 */
	protected $name;


	public function setName(string $name)
	{
		$this->name = $name;

		return $this;
	}


	public function name()
	{
		return $this->name;
	}

	/**
	 * Adds a single item to the menu.
	 *
	 * @param StatsItem $item
	 *
	 * @return $this
	 */
	public function addItem(StatsItem $item): StatsCollection
	{
		$this->items[]  = $item;

		return $this;
	}

	/**
	 * Add multiple items at once.
	 *
	 * @param array $items
	 *
	 * @return $this
	 */
	public function addItems(array $items): StatsCollection
	{
		$this->items = array_merge($this->items, $items);

		return $this;
	}

	/**
	 * @param string $title
	 */
	public function removeItem(string $title)
	{
		for ($i = 0; $i < count($this->items); $i++) {
			if ($this->items[$i]->title() === $title) {
				unset($this->items[$i]);
				break;
			}
		}
	}

	/**
	 * Removes all of the items from this collection.
	 *
	 * @return $this
	 */
	public function removeAllItems(): StatsCollection
	{
		$this->items = [];

		return $this;
	}

	/**
	 * Returns all items in the Collection,
	 * sorted by weight, where larger weights
	 * make them fall to the bottom.
	 *
	 * @return array
	 */
	public function items(): array
	{
		$this->sortItems();

		return $this->items;
	}

	/**
	 * Sorts the items by the weight,
	 * ensuring that bigger weights
	 * drop to the bottom.
	 */
	protected function sortItems()
	{
		usort($this->items, function ($a, $b) {
			if ($a->weight === $b->weight) {
				return $a->title <=> $b->title;
			}

			return $a->weight <=> $b->weight;
		});
	}

	public function __get(string $key)
	{
		if (method_exists($this, $key)) {
			return $this->{$key}();
		}
	}

}