<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\View;

/**
 * Class BaseComponent
 *
 * Provides the basic functionality used when rendering a
 * view component. This includes everything needed to render
 * a component that does not have a class associated with it.
 */
class Component
{
    /**
     * All collected attributes for the tag
     *
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
     */
    public function withView(string $view): Component
    {
        $this->view = $view;

        return $this;
    }

    /**
     * Set the data that should be passed along to the view.
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
     */
    public function render(): string
    {
        return $this->renderView($this->view, []);
    }

    /**
     * Renders the view when no corresponding class has been found.
     */
    protected function renderView(string $view, array $data): string
    {
        return (static function (string $view, $data) {
            extract($data);
            ob_start();
            eval('?>' . file_get_contents($view));
            return ob_get_clean() ?: '';
        })($view, $data);
    }
}
