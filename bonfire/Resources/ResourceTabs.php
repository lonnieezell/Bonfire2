<?php

namespace Bonfire\Resources;

/**
 * Provides ways to manage the tabs that display for a
 * resource, like a User, a User Group etc. Lets non-Bonfire
 * code integrate tabs seamlessly.
 */
class ResourceTabs
{
    /**
     * @var array
     */
    public $tabs = [];

    /**
     * Adds a new Resource Tab to the given resource.
     *
     * @param string      $resource
     * @param ResourceTab $tab
     *
     * @return ResourceTabs
     */
    public function addTabFor(string $resource, ResourceTab $tab): ResourceTabs
    {
        $resource = strtolower($resource);

        if (! array_key_exists($resource, $this->tabs)) {
            $this->tabs[$resource] = [];
        }

        $this->tabs[$resource][] = $tab;

        return $this;
    }

    /**
     * Renders the extra tabs for a single resource.
     *
     * @param string $resource
     *
     * @return string
     */
    public function renderTabsFor(string $resource): string
    {
        $resource = strtolower($resource);

        if (! isset($this->tabs[$resource]) || empty($this->tabs[$resource])) {
            return '';
        }

        return view('\Bonfire\Views\_resource_tabs', ['tabs' => $this->tabs[$resource]]);
    }
}
