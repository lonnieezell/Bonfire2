<?php

namespace Bonfire\View;

/**
 * Class BaseComponent
 *
 * Provides the basic functionality used when rendering a
 * view component. This includes everything needed to render
 * a component that does not have a class associated with it.
 *
 * @package Bonfire\View
 */
class Component
{
    /**
     * All collected attributes for the tag
     * @var string
     */
    protected $attributes;

    /**
     * The values that can be used
     *
     * @var array
     */
    protected $data = [];

    /**
     * @var string
     */
    protected $view;

    /**
     * Stores the view name.
     *
     * @param string $view
     */
    public function withView(string $view): Component
    {
        $this->view = $view;

        return $this;
    }

    /**
     * Set the data that should be passed along to the view.
     *
     * @param array $data
     *
     * @return $this
     */
    public function withData(array $data): Component
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Returns the processed component view.
     *
     * @return string
     */
    public function render(): string
    {
        return $this->renderView($this->view, []);
    }

    /**
     * Renders the view when no corresponding class has been found.
     *
     * @param string $view
     * @param array  $data
     *
     * @return string|null
     */
    protected function renderView(string $view, array $data): string
    {
        return (function (string $view, $data) {
            extract($data);
            ob_start();
            eval('?>' . file_get_contents($view));
            return ob_get_clean() ?: '';
        })($view, $data);
    }
}
