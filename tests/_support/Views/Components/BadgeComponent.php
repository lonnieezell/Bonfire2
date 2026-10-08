<?php

use Bonfire\View\Component;

/**
 * Fixture: a component class that reads its attributes from $this->data.
 */
class BadgeComponent extends Component
{
    public function render(): string
    {
        return $this->renderView($this->view, [
            'label' => $this->data['label'] ?? 'none',
        ]);
    }
}
