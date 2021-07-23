<?php

use Bonfire\View\Component;

/**
 * Class SidebarComponent
 *
 * Uses the MenuManager to get the registered menu items
 * and render out the sidebar.
 */
class SidebarComponent extends Component
{
    /**
     * @return string
     */
    public function render(): string
    {
        return $this->renderView($this->view, [
            'menu' => service('menus')->menu('sidebar'),
        ]);
    }
}
