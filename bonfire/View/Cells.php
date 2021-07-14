<?php

namespace Bonfire\View;

class Cells
{
	/**
	 * Builds and displays the sidebar navigation for the admin area.
	 *
	 * @return string
	 */
	public function sidebarNav()
	{
		return view('Bonfire\Assets\Views\sidebar');
	}
}
