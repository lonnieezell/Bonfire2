<?php namespace Bonfire\Libraries\Menus;

/**
 * Represents a collection of menu items.
 * This is used to store the dropdown button for dropdown menus,
 * or the header for accordion-style menus.
 *
 * @package Bonfire\Menus
 */
class MenuCollection extends MenuItem
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

	/**
	 * Sets the name this collection can be referenced by.
	 *
	 * @param string $name
	 *
	 * @return $this
	 */
	public function setName(string $name)
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * @return string
	 */
	public function name()
	{
		return $this->name;
	}

	/**
	 * Adds a single item to the menu.
	 *
	 * @param MenuItem $item
	 *
	 * @return $this
	 */
	public function addItem(MenuItem $item)
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
	public function addItems(array $items)
	{
		$this->items = array_merge($this->items, $items);

		return $this;
	}

	/**
	 * @param string $title
	 */
	public function removeItem(string $title)
	{
		for ($i = 0; $i < count($this->items); $i++)
		{
			if ($this->items[$i]->title() === $title)
			{
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
	public function removeAllItems()
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
	public function items()
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
		usort($this->items, function($a, $b) {
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
